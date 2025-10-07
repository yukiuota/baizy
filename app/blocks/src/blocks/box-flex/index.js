/**
 * 画像とテキストの横並びブロック（複数追加可能）
 */
import { registerBlockType } from "@wordpress/blocks";
import { RichText, useBlockProps, MediaPlaceholder, MediaUpload, MediaUploadCheck } from "@wordpress/block-editor";
import { Button, ButtonGroup, PanelBody } from "@wordpress/components";
import { Fragment } from "@wordpress/element";

// ブロック登録
registerBlockType("my-blocks/box-flex", {
  title: "画像横並びブロック",
  icon: "layout",
  description: "画像とテキストが横並びになるブロックです（複数追加可能）",
  category: "theme-custom",
  example: {},
  attributes: {
    items: {
      type: "array",
      default: [
        {
          id: 0,
          imageId: 0,
          imageUrl: "",
          headingText: "見出しテキスト",
          contentText: "説明文をここに入力します",
          contentText02: "補足説明をここに入力します",
        },
      ],
    },
  },

  // 編集画面の表示
  edit: ({ attributes, setAttributes }) => {
    const { items } = attributes;
    const blockProps = useBlockProps();

    const addNewItem = () => {
      const newItem = {
        id: new Date().getTime(),
        imageId: 0,
        imageUrl: "",
        headingText: "見出しテキスト",
        contentText: "説明文をここに入力します",
        contentText02: "補足説明をここに入力します",
      };

      setAttributes({
        items: [...items, newItem],
      });
    };

    const removeItem = (index) => {
      const newItems = [...items];
      newItems.splice(index, 1);
      setAttributes({ items: newItems });
    };

    const updateItemAttribute = (index, attribute, value) => {
      const newItems = [...items];
      newItems[index] = {
        ...newItems[index],
        [attribute]: value,
      };
      setAttributes({ items: newItems });
    };

    const onSelectImage = (index, media) => {
      updateItemAttribute(index, "imageId", media.id);
      updateItemAttribute(index, "imageUrl", media.url);
    };

    const onRemoveImage = (index) => {
      updateItemAttribute(index, "imageId", 0);
      updateItemAttribute(index, "imageUrl", "");
    };

    return (
      <div {...blockProps}>
        {items.map((item, index) => (
          <Fragment key={item.id || index}>
            <div className="box-flex">
              <div className="box-flex__img">
                {!item.imageUrl ? (
                  <MediaPlaceholder
                    onSelect={(media) => onSelectImage(index, media)}
                    allowedTypes={["image"]}
                    multiple={false}
                    labels={{ title: "画像を追加" }}
                  />
                ) : (
                  <MediaUploadCheck>
                    <div className="image-wrapper">
                      <img src={item.imageUrl} alt="" />
                      <MediaUpload
                        onSelect={(media) => onSelectImage(index, media)}
                        allowedTypes={["image"]}
                        value={item.imageId}
                        render={({ open }) => (
                          <Button onClick={open} className="image-button is-edit" isPrimary>
                            画像を変更
                          </Button>
                        )}
                      />
                      <Button onClick={() => onRemoveImage(index)} className="image-button is-remove" isDestructive>
                        画像を削除
                      </Button>
                    </div>
                  </MediaUploadCheck>
                )}
              </div>
              <div className="box-flex__textarea">
                <RichText
                  tagName="h2"
                  className="box-flex__ttl"
                  value={item.headingText}
                  onChange={(value) => updateItemAttribute(index, "headingText", value)}
                  placeholder="見出しを入力"
                />
                <RichText
                  tagName="p"
                  className="box-flex__text"
                  value={item.contentText}
                  onChange={(value) => updateItemAttribute(index, "contentText", value)}
                  placeholder="テキストを入力"
                />
                <RichText
                  tagName="p"
                  className="box-flex__text box-flex__text--02"
                  value={item.contentText02}
                  onChange={(value) => updateItemAttribute(index, "contentText02", value)}
                  placeholder="補足説明を入力"
                />
              </div>
            </div>
            {items.length > 1 && (
              <div className="box-flex-item-controls">
                <Button onClick={() => removeItem(index)} isDestructive>
                  この項目を削除
                </Button>
              </div>
            )}
            <hr style={{ margin: "20px 0" }} />
          </Fragment>
        ))}

        <div className="box-flex-controls">
          <Button onClick={addNewItem} isPrimary className="box-flex-add-button">
            新しい項目を追加
          </Button>
        </div>
      </div>
    );
  },

  // フロント表示
  save: ({ attributes }) => {
    const { items } = attributes;

    return (
      <div className="box-flex-container">
        {items.map((item, index) => (
          <div key={index} className="box-flex">
            <div className="box-flex__img">{item.imageUrl && <img src={item.imageUrl} alt="" />}</div>
            <div className="box-flex__textarea">
              <RichText.Content tagName="h2" className="box-flex__ttl" value={item.headingText} />
              <RichText.Content tagName="p" className="box-flex__text" value={item.contentText} />
              <RichText.Content tagName="p" className="box-flex__text02" value={item.contentText02} />
            </div>
          </div>
        ))}
      </div>
    );
  },

  // 旧バージョンとの互換性のための設定
  deprecated: [
    {
      attributes: {
        items: {
          type: "array",
          default: [
            {
              id: 0,
              imageId: 0,
              imageUrl: "",
              headingText: "見出しテキスト",
              contentText: "説明文をここに入力します",
            },
          ],
        },
      },
      save: ({ attributes }) => {
        const { items } = attributes;

        return (
          <div className="box-flex-container">
            {items.map((item, index) => (
              <div key={index} className="box-flex">
                <div className="box-flex__img">{item.imageUrl && <img src={item.imageUrl} alt="" />}</div>
                <div className="box-flex__textarea">
                  <RichText.Content tagName="h2" className="box-flex__ttl" value={item.headingText} />
                  <RichText.Content tagName="p" className="box-flex__text" value={item.contentText} />
                </div>
              </div>
            ))}
          </div>
        );
      },
      // 古いブロックから新しいブロックへの変換
      migrate: (attributes) => {
        const newItems = attributes.items.map((item) => ({
          ...item,
          contentText02: "補足説明をここに入力します",
        }));

        return {
          items: newItems,
        };
      },
    },
  ],
});
