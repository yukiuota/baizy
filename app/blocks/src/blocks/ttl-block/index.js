/**
 * タイトルサンプルブロック
 */
import { registerBlockType } from "@wordpress/blocks";
import { RichText, useBlockProps } from "@wordpress/block-editor";

// ブロック登録
registerBlockType("my-blocks/ttl-block", {
  title: "タイトルサンプルブロック",
  icon: "smiley",
  description: "これはタイトル用のテスト用ブロックです",
  category: "theme-custom",
  example: {},
  attributes: {
    headingText: {
      type: "string",
      source: "text",
      selector: "h2.ttl01",
      default: "すきなテキスト",
    },
  },

  // 編集画面の表示
  edit: ({ attributes, setAttributes }) => {
    const { headingText } = attributes;
    const blockProps = useBlockProps();

    const onChangeHeadingText = (newText) => {
      setAttributes({
        headingText: newText,
      });
    };

    return (
      <div {...blockProps}>
        <RichText tagName="h2" className="ttl01" value={headingText} onChange={onChangeHeadingText} />{" "}
      </div>
    );
  },

  // フロント表示
  save: ({ attributes }) => {
    const { headingText } = attributes;

    return <RichText.Content tagName="h2" className="ttl01" value={headingText} />;
  },
});
