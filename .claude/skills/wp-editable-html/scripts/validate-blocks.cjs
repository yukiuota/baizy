#!/usr/bin/env node
/**
 * カスタム HTML ブロック（wp:html）用に書いたブロックマークアップを、ローカル WordPress のエディター実機で検証する
 *
 * 使い方:
 *   node .claude/skills/wp-editable-html/scripts/validate-blocks.cjs <blocks.html> [--compare <original.html>] [--url <URL>]
 *
 * 確認すること:
 *   1. 各ブロックがエディターの検証を通るか（通らないと貼り付け時に「予期しないコンテンツ」エラーになる）
 *   2. エディターで読み込んで保存し直した結果が入力と一致するか
 *      isValid が true でも、旧形式として読み込まれて保存時に別の HTML へ書き換わる（<p><p data-x> のように壊れる）ことがあるため
 *   3. wp:html の外にはみ出した HTML（クラシックブロック扱いになる）や、未登録のブロック名がないか
 *   --compare 指定時は、区切りコメントを除いた HTML と元の HTML の DOM を比べて差分を出す（参考情報。終了コードには影響しない）
 *   WordPress が付けるクラス（wp-block-* / wp-element-*）と、表示時に付く loading / decoding / fetchpriority は比較から除く
 *
 * ログイン:
 *   WP_USER / WP_PASS があればログインフォームから入る。なければ docker compose の wpcli サービスで
 *   管理者の一時的なログイン Cookie（15 分で失効）を発行する。compose ファイルの場所は BAIZY_COMPOSE_FILE で変えられる
 *
 * エディター（post-new.php）を開くため、実行のたびに自動下書きが 1 件できる（WordPress が 7 日後に自動で削除する）
 */

const fs = require("fs");
const os = require("os");
const path = require("path");
const { execFileSync, spawnSync } = require("child_process");
const puppeteer = require("puppeteer");

const THEME_DIR = path.resolve(__dirname, "../../../..");

const parseArgs = (argv) => {
  const args = { file: null, compare: null, url: process.env.BAIZY_WP_URL || "http://localhost:8080" };
  for (let i = 0; i < argv.length; i++) {
    if (argv[i] === "--compare") args.compare = argv[++i];
    else if (argv[i] === "--url") args.url = argv[++i];
    else args.file = argv[i];
  }
  args.url = args.url.replace(/\/$/, "");
  return args;
};

// WP-CLI でログイン Cookie を発行する（パスワードを知らなくても検証できるように）
const cookiesFromWpCli = () => {
  const composeFile = process.env.BAIZY_COMPOSE_FILE || path.join(THEME_DIR, "../../docker-compose.yml");
  if (!fs.existsSync(composeFile)) {
    throw new Error(`docker-compose.yml が見つかりません（${composeFile}）。WP_USER / WP_PASS を指定してください`);
  }
  const php = `
    $users = get_users( array( 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID' ) );
    if ( ! $users ) { fwrite( STDERR, '管理者ユーザーがいません' ); exit( 1 ); }
    $id    = $users[0]->ID;
    $exp   = time() + 15 * MINUTE_IN_SECONDS;
    $token = WP_Session_Tokens::get_instance( $id )->create( $exp );
    echo wp_json_encode( array(
      array( 'name' => AUTH_COOKIE, 'value' => wp_generate_auth_cookie( $id, $exp, 'auth', $token ) ),
      array( 'name' => SECURE_AUTH_COOKIE, 'value' => wp_generate_auth_cookie( $id, $exp, 'secure_auth', $token ) ),
      array( 'name' => LOGGED_IN_COOKIE, 'value' => wp_generate_auth_cookie( $id, $exp, 'logged_in', $token ) ),
    ) );`;
  const out = execFileSync(
    "docker",
    ["compose", "-f", composeFile, "run", "--rm", "--no-deps", "-T", "wpcli", "wp", "eval", php],
    { encoding: "utf8", stdio: ["ignore", "pipe", "pipe"] },
  );
  return JSON.parse(out.trim().split("\n").pop());
};

const login = async (page, baseUrl) => {
  if (process.env.WP_USER && process.env.WP_PASS) {
    await page.goto(`${baseUrl}/wp-login.php`, { waitUntil: "domcontentloaded" });
    await page.type("#user_login", process.env.WP_USER);
    await page.type("#user_pass", process.env.WP_PASS);
    await Promise.all([page.waitForNavigation(), page.click("#wp-submit")]);
    return;
  }
  const { hostname } = new URL(baseUrl);
  await page.setCookie(...cookiesFromWpCli().map((c) => ({ ...c, domain: hostname, path: "/" })));
};

const diff = (labelA, textA, labelB, textB) => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), "baizy-blocks-"));
  const a = path.join(dir, "a");
  const b = path.join(dir, "b");
  fs.writeFileSync(a, textA.endsWith("\n") ? textA : `${textA}\n`);
  fs.writeFileSync(b, textB.endsWith("\n") ? textB : `${textB}\n`);
  const res = spawnSync("diff", ["-u", "--label", labelA, "--label", labelB, a, b], { encoding: "utf8" });
  fs.rmSync(dir, { recursive: true, force: true });
  return res.stdout;
};

const indent = (text, prefix = "      ") =>
  String(text)
    .split("\n")
    .map((l) => prefix + l)
    .join("\n");

// --- 以下はエディターのページ内で実行する ---

const inspectInEditor = (input) => {
  const { parse, serialize, getSaveContent } = wp.blocks;

  // 7.1 未満は wp:html の中のブロックを扱えないため、最初に機能の有無を確かめる
  const probe = "<!-- wp:html -->\n<div><!-- wp:paragraph -->\n<p>x</p>\n<!-- /wp:paragraph --></div>\n<!-- /wp:html -->";
  const probed = parse(probe);
  const supported = probed[0]?.innerBlocks.length === 1 && serialize(probed) === probe;

  const textOf = (html) => {
    const div = document.createElement("div");
    div.innerHTML = html || "";
    return div.textContent.replace(/\s+/g, " ").trim().slice(0, 30);
  };

  const blocks = parse(input);
  const problems = [];
  const visit = (block, label) => {
    if (block.name === "core/freeform") {
      problems.push({ label, type: "freeform", detail: String(block.attributes.content).trim().slice(0, 300) });
    } else if (block.name === "core/missing") {
      problems.push({ label, type: "missing", detail: block.attributes.originalName });
    } else if (block.isValid === false) {
      problems.push({
        label,
        type: "invalid",
        name: block.name,
        expected: getSaveContent(block.name, block.attributes, block.innerBlocks),
        actual: block.originalContent,
      });
    }
    block.innerBlocks.forEach((child, i) => visit(child, `${label} > ${i + 1}`));
  };
  blocks.forEach((block, i) => visit(block, `[${i + 1}]`));

  const summary = blocks.map((block, i) => {
    const counts = {};
    block.innerBlocks.forEach((child) => (counts[child.name] = (counts[child.name] || 0) + 1));
    return {
      label: `[${i + 1}]`,
      name: block.name,
      inner: Object.entries(counts).map(([name, n]) => `${name.replace("core/", "")}×${n}`),
      text: textOf(serialize([block]).replace(/<!--[\s\S]*?-->/g, "")),
    };
  });

  return JSON.stringify({ supported, summary, problems, reserialized: serialize(blocks) });
};

// 比較用に DOM を「1 行 1 ノード」のテキストへ正規化する
// 階層をインデントで表すと、ラッパーを 1 つ足し引きしただけで配下が全部差分になるため、あえてフラットに並べる
const normalizeDomInEditor = (htmlList) => {
  const IGNORE_ATTRS = new Set(["class", "loading", "decoding", "fetchpriority"]);
  return htmlList.map((html) => {
    const doc = new DOMParser().parseFromString(`<body>${html}</body>`, "text/html");
    const lines = [];
    const walk = (node) => {
      for (const child of node.childNodes) {
        if (child.nodeType === Node.TEXT_NODE) {
          const text = child.textContent.replace(/\s+/g, " ").trim();
          if (text) lines.push(`"${text}"`);
        } else if (child.nodeType === Node.ELEMENT_NODE) {
          const classes = [...child.classList].filter((c) => !/^wp-(block|element)-/.test(c)).sort();
          const attrs = [...child.attributes]
            .filter((a) => !IGNORE_ATTRS.has(a.name))
            .map((a) => `${a.name}="${a.value}"`)
            .sort();
          const cls = classes.length ? `.${classes.join(".")}` : "";
          lines.push(`<${child.tagName.toLowerCase()}${cls}${attrs.length ? ` ${attrs.join(" ")}` : ""}>`);
          walk(child);
        }
      }
    };
    walk(doc.body);
    return lines.join("\n");
  });
};

// --- ここまで ---

const main = async () => {
  const args = parseArgs(process.argv.slice(2));
  if (!args.file) {
    console.error("使い方: node validate-blocks.cjs <blocks.html> [--compare <original.html>] [--url <URL>]");
    process.exit(2);
  }
  const input = fs.readFileSync(args.file, "utf8").replace(/\r\n/g, "\n");
  console.log(`==> ${args.file} を ${args.url} のエディターで検証します`);

  const browser = await puppeteer.launch({ headless: true });
  let failed = 0;
  try {
    const page = await browser.newPage();
    await login(page, args.url);
    await page.goto(`${args.url}/wp-admin/post-new.php?post_type=page`, { waitUntil: "domcontentloaded" });
    if (page.url().includes("wp-login.php")) {
      throw new Error("ログインできませんでした（WP_USER / WP_PASS か docker の状態を確認してください）");
    }
    await page.waitForFunction(() => window.wp?.blocks?.getBlockType("core/html"), { timeout: 60000 });

    const result = JSON.parse(await page.evaluate(inspectInEditor, input));
    if (!result.supported) {
      throw new Error("この WordPress は wp:html の中のブロックに対応していません（WordPress 7.1 以上が必要）");
    }

    for (const s of result.summary) {
      const inner = s.inner.length ? `  編集可能: ${s.inner.join(", ")}` : "";
      console.log(`    ${s.label} ${s.name}${inner}  「${s.text}…」`);
    }

    for (const p of result.problems) {
      failed++;
      if (p.type === "freeform") {
        console.log(`NG  ${p.label} ブロックの外に HTML があります（クラシックブロック扱いになる）\n${indent(p.detail)}`);
      } else if (p.type === "missing") {
        console.log(`NG  ${p.label} 未登録のブロックです: ${p.detail}`);
      } else {
        console.log(`NG  ${p.label} ${p.name} が検証エラーです`);
        console.log(diff("入力", p.actual, "エディターが期待する保存形式", p.expected).replace(/^/gm, "      "));
      }
    }

    // 区切りの空行の数だけ違うものは同一とみなす（serialize はトップレベルを空行 1 つで連結する）
    const tidy = (s) => s.trim().replace(/\n{3,}/g, "\n\n");
    if (tidy(result.reserialized) !== tidy(input)) {
      failed++;
      console.log("NG  エディターで保存し直すと内容が変わります（- が入力、+ が保存される内容）");
      console.log(diff("入力", tidy(input), "保存される内容", tidy(result.reserialized)).replace(/^/gm, "      "));
    }

    if (args.compare) {
      const original = fs.readFileSync(args.compare, "utf8");
      const front = input.replace(/<!--\s+\/?wp:[\s\S]*?-->/g, "");
      const [a, b] = await page.evaluate(normalizeDomInEditor, [original, front]);
      const d = diff(`元の HTML（${path.basename(args.compare)}）`, a, "ブロック化後（フロント相当）", b);
      console.log("");
      if (d) {
        console.log("==> 元の HTML との DOM 差分（意図した変更だけか確認する。テキストの差分は 0 であること）");
        console.log(d.replace(/^/gm, "    "));
      } else {
        console.log("==> 元の HTML との DOM 差分なし");
      }
    }
  } finally {
    await browser.close();
  }

  console.log(`\n==> 検証: ${failed === 0 ? "OK" : `NG ${failed} 件`}`);
  process.exit(failed === 0 ? 0 : 1);
};

main().catch((e) => {
  console.error(e.message || e);
  process.exit(2);
});
