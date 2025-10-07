# Chrome DevTools MCP セットアップガイド

## 概要

Chrome DevTools MCPは、AIコーディングアシスタント（Gemini、Claude、Cursor、Copilot など）がライブのChromeブラウザを制御・検査できるようにするModel Context Protocol（MCP）サーバーです。

## インストール済みコンテンツ

- `chrome-devtools-mcp` パッケージ（バージョン 0.3.0）
- 設定ファイル：
  - `mcp-servers.json` - 基本設定
  - `mcp-config.json` - 詳細設定オプション付き

## 利用可能なスクリプト

以下のnpmスクリプトが追加されています：

```bash
# 基本的なMCPサーバー起動
npm run mcp:start

# ヘッドレスモードで起動（UIなし）
npm run mcp:headless

# 開発者チャンネルのChromeを使用
npm run mcp:dev
```

## VS Code での使用方法

### 1. GitHub Copilot での使用

VS CodeでGitHub Copilotを使用している場合、以下の手順で設定できます：

1. VS Codeの設定を開く（Cmd/Ctrl + ,）
2. "github.copilot.mcp" を検索
3. MCP設定に以下を追加：

```json
{
  "mcpServers": {
    "chrome-devtools": {
      "command": "npx",
      "args": ["chrome-devtools-mcp@latest"]
    }
  }
}
```

### 2. Cursor での使用

Cursorエディターを使用している場合：

1. Cursorの設定 > Extensions > MCP
2. 設定ファイルに `mcp-config.json` の内容を追加

## 主な機能

### 入力自動化（7ツール）
- click - 要素をクリック
- drag - ドラッグ操作
- fill - フォーム入力
- fill_form - フォーム一括入力
- handle_dialog - ダイアログ処理
- hover - ホバー操作
- upload_file - ファイルアップロード

### ナビゲーション自動化（7ツール）
- close_page - ページを閉じる
- list_pages - ページ一覧
- navigate_page - ページナビゲーション
- new_page - 新規ページ
- select_page - ページ選択
- wait_for - 待機

### パフォーマンス（3ツール）
- performance_analyze_insight - パフォーマンス解析
- performance_start_trace - トレース開始
- performance_stop_trace - トレース停止

### デバッグ（4ツール）
- evaluate_script - スクリプト実行
- list_console_messages - コンソールメッセージ一覧
- take_screenshot - スクリーンショット
- take_snapshot - スナップショット

## 設定オプション

### 基本設定
- `--headless`: ヘッドレスモード（UIなし）
- `--isolated`: 一時的なユーザーデータディレクトリを使用
- `--channel`: Chromeチャンネル選択（stable, canary, beta, dev）
- `--executablePath`: カスタムChrome実行パスを指定

### 使用例

```json
{
  "mcpServers": {
    "chrome-devtools-production": {
      "command": "npx",
      "args": [
        "chrome-devtools-mcp@latest",
        "--headless=true",
        "--isolated=true"
      ]
    },
    "chrome-devtools-dev": {
      "command": "npx", 
      "args": [
        "chrome-devtools-mcp@latest",
        "--channel=canary",
        "--isolated=true"
      ]
    }
  }
}
```

## テスト方法

MCPクライアントで以下のプロンプトを入力してテストしてください：

```
Check the performance of https://developers.chrome.com
```

これにより、ブラウザが自動的に開かれ、パフォーマンストレースが記録されます。

## 注意事項

- `chrome-devtools-mcp` はブラウザインスタンスの内容をMCPクライアントに公開するため、機密情報や個人情報を含むページでの使用は避けてください。
- macOS Seatbelt や Linuxコンテナなどのサンドボックス環境では制限がある場合があります。
- MCP サーバーはブラウザが必要になった時点で自動的に Chrome を起動します。

## トラブルシューティング

### ブラウザが起動しない場合
1. Node.js のバージョンを確認（22.12.0以上が必要）
2. Chrome が正しくインストールされているか確認
3. `--executablePath` でChromeのパスを明示的に指定

### サンドボックスエラーの場合
- `--isolated=true` オプションを使用
- MCP クライアントのサンドボックス設定を無効化
- 手動でChromeを起動し、`--browserUrl` で接続

## 参考リンク

- [Chrome DevTools MCP GitHub](https://github.com/ChromeDevTools/chrome-devtools-mcp)
- [ツールリファレンス](https://github.com/ChromeDevTools/chrome-devtools-mcp/blob/main/docs/tool-reference.md)
- [npm パッケージ](https://npmjs.org/package/chrome-devtools-mcp)