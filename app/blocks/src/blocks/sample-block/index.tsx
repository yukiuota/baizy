/**
 * サンプルリストブロック
 */
import { registerBlockType } from "@wordpress/blocks";
import { RichText, useBlockProps } from "@wordpress/block-editor";
import { Button } from "@wordpress/components";

interface Item {
  content: string;
}

interface Attributes {
  items: Item[];
}

// ブロック登録
registerBlockType<Attributes>("my-blocks/sample-block", {
  title: "サンプルブロック",
  icon: "heart",
  description: "これはテスト用のブロックです",
  category: "theme-custom",
  example: {},
  attributes: {
    items: {
      type: "array",
      source: "query",
      selector: "li",
      default: [
        {
          content: "これはサンプルブロックです。（edit）",
        },
      ],
      query: {
        content: {
          type: "string",
          source: "html",
        },
      },
    },
  } as any, // 型定義の不整合を避けるため一旦 any キャストするか、厳密に定義する

  // 編集画面の表示
  edit: ({ attributes, setAttributes }: { attributes: Attributes; setAttributes: (attrs: Partial<Attributes>) => void }) => {
    const { items } = attributes;
    const blockProps = useBlockProps({ className: "area" });

    const onChangeItemContent = (newContent: string, index: number) => {
      const newItems = items.map((item, i) => {
        if (i === index) {
          return {
            content: newContent,
          };
        }
        return item;
      });
      setAttributes({ items: newItems });
    };

    const addItem = () => {
      const newItems = [
        ...items,
        {
          content: "新しいアイテム",
        },
      ];
      setAttributes({ items: newItems });
    };

    const removeItem = (index: number) => {
      const newItems = items.filter((item, i) => i !== index);
      setAttributes({ items: newItems });
    };

    return (
      <div {...blockProps}>
        <div>
          <ul className="list">
            {items.map((item, index) => (
              <li className="list__item" key={index}>
                <RichText
                  tagName="span"
                  value={item.content}
                  onChange={(newContent) => onChangeItemContent(newContent, index)}
                />
                <Button className="remove-item-button" onClick={() => removeItem(index)}>
                  削除
                </Button>
              </li>
            ))}
          </ul>
          <Button className="add-item-button" onClick={addItem}>
            アイテムを追加
          </Button>
        </div>
      </div>
    );
  },

  // フロント表示
  save: ({ attributes }: { attributes: Attributes }) => {
    const { items } = attributes;

    return (
      <div className="list-area1">
        <div className="list-area2">
          <ul className="list">
            {items.map((item, index) => (
              <RichText.Content tagName="li" className="list__item" value={item.content} key={index} />
            ))}
          </ul>
        </div>
      </div>
    );
  },
});
