/**
 * タイトルサンプルブロック
 */
import { registerBlockType } from "@wordpress/blocks";
import { RichText, useBlockProps } from "@wordpress/block-editor";

interface Attributes {
  headingText: string;
}

// ブロック登録
registerBlockType<Attributes>("my-blocks/ttl-block", {
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
  } as any,

  // 編集画面の表示
  edit: ({ attributes, setAttributes }: { attributes: Attributes; setAttributes: (attrs: Partial<Attributes>) => void }) => {
    const { headingText } = attributes;
    const blockProps = useBlockProps();

    const onChangeHeadingText = (newText: string) => {
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
  save: ({ attributes }: { attributes: Attributes }) => {
    const { headingText } = attributes;

    return <RichText.Content tagName="h2" className="ttl01" value={headingText} />;
  },
});
