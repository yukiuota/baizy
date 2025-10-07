/**
 * Q&Aブロック
 */
import { registerBlockType } from "@wordpress/blocks";
import { RichText, useBlockProps } from "@wordpress/block-editor";
import { Button, PanelBody, TextControl } from "@wordpress/components";
import { InspectorControls } from "@wordpress/block-editor";

// ブロック登録
registerBlockType("my-blocks/qa-block", {
  title: "Q&Aブロック",
  icon: "format-chat",
  description: "質問と回答をセットで表示するブロックです",
  category: "theme-custom",
  example: {},
  attributes: {
    qaPairs: {
      type: "array",
      default: [
        {
          question: "",
          answer: "",
        },
      ],
    },
  },

  // 編集画面の表示
  edit: ({ attributes, setAttributes }) => {
    const { qaPairs } = attributes;
    const blockProps = useBlockProps({
      className: "qa-block",
    });

    const updateQAPair = (index, field, value) => {
      const newQAPairs = qaPairs.map((pair, i) => {
        if (i === index) {
          return {
            ...pair,
            [field]: value,
          };
        }
        return pair;
      });
      setAttributes({
        qaPairs: newQAPairs,
      });
    };

    const addQAPair = () => {
      const newQAPairs = [
        ...qaPairs,
        {
          question: "",
          answer: "",
        },
      ];
      setAttributes({
        qaPairs: newQAPairs,
      });
    };

    const removeQAPair = (index) => {
      const newQAPairs = qaPairs.filter((pair, i) => i !== index);
      setAttributes({
        qaPairs: newQAPairs,
      });
    };

    return (
      <>
        <InspectorControls>
          <PanelBody title="Q&A設定">
            <p> Q & Aペアの数: {qaPairs.length} </p>{" "}
          </PanelBody>{" "}
        </InspectorControls>
        <div {...blockProps}>
          <div className="qa-block__container">
            {" "}
            {qaPairs.map((pair, index) => (
              <div key={index} className="qa-block__item">
                <div className="qa-block__question-wrapper">
                  <div className="qa-block__question-label"> Q {index + 1} </div>{" "}
                  <RichText
                    tagName="div"
                    className="qa-block__question"
                    value={pair.question}
                    onChange={(value) => updateQAPair(index, "question", value)}
                    placeholder="質問を入力してください"
                  />
                </div>
                <div className="qa-block__answer-wrapper">
                  <div className="qa-block__answer-label"> A {index + 1} </div>{" "}
                  <RichText
                    tagName="div"
                    className="qa-block__answer"
                    value={pair.answer}
                    onChange={(value) => updateQAPair(index, "answer", value)}
                    placeholder="回答を入力してください"
                  />
                </div>
                {qaPairs.length > 1 && (
                  <Button className="qa-block__remove-button" onClick={() => removeQAPair(index)} isDestructive>
                    このQ & Aを削除{" "}
                  </Button>
                )}{" "}
              </div>
            ))}
            <Button className="qa-block__add-button" onClick={addQAPair} isPrimary>
              Q & Aを追加{" "}
            </Button>{" "}
          </div>{" "}
        </div>{" "}
      </>
    );
  },

  // フロント表示
  save: ({ attributes }) => {
    const { qaPairs } = attributes;

    return (
      <div className="qa-block">
        <div className="qa-block__container">
          {" "}
          {qaPairs.map((pair, index) => (
            <div key={index} className="qa-block__item">
              <div className="qa-block__question-wrapper">
                <div className="qa-block__question-label"> Q {index + 1} </div>{" "}
                <RichText.Content tagName="div" className="qa-block__question" value={pair.question} />{" "}
              </div>
              <div className="qa-block__answer-wrapper">
                <div className="qa-block__answer-label"> A {index + 1} </div>{" "}
                <RichText.Content tagName="div" className="qa-block__answer" value={pair.answer} />{" "}
              </div>{" "}
            </div>
          ))}{" "}
        </div>{" "}
      </div>
    );
  },
});
