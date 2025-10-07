/**
 * サンプルリストブロック
 */
import { registerBlockType } from "@wordpress/blocks";
import { RichText, useBlockProps } from "@wordpress/block-editor";
import { Button } from "@wordpress/components";

// ブロック登録
registerBlockType("my-blocks/sample-block", {
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
  },

  // 編集画面の表示
  edit: ({ attributes, setAttributes }) => {
    const { items } = attributes;
    const blockProps = useBlockProps({ className: "area" });

    const onChangeItemContent = (newContent, index) => {
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

    const removeItem = (index) => {
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
  save: ({ attributes }) => {
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
