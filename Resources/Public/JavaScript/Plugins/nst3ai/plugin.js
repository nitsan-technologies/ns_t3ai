CKEDITOR.dialog.add("nsT3AiContentDialog", function(editor) {
    let select_model = "gpt-3.5-turbo-instruct",
        select_temperature = 0.5,
        select_max_tokens = 4e3,
        select_amount = 1;
    const escapeHtml = (unsafe) => {
        return unsafe.replaceAll("&", "&amp;").replaceAll("<", "&lt;").replaceAll(">", "&gt;").replaceAll('"', "&quot;").replaceAll("'", "&#039;");
    };
    return {
        title: "T3AI Content Assistance",
        minWidth: 400,
        minHeight: 70,
        contents: [
            {
                id: "ns-tab-basic",
                label: editor.lang.nst3ai_content.tabGeneral,
                accessKey: "C",
                elements: [
                    {
                        type: "text",
                        id: "ns-t3ai-prompt",
                        label: editor.lang.nst3ai_content.writeAbout,
                        rows: 2,
                        validate: CKEDITOR.dialog.validate.notEmpty(editor.lang.nst3ai_content.errorNotEmpty),
                        setup: function(element) {
                            this.setValue(element.getText());
                        },
                        commit: function(element) {
                            let generatedContent = this.getDialog().getContentElement('ns-tab-basic', 'ns-t3ai-generated-content').getValue();
                            element.setHtml(generatedContent);
                        }
                    },
                    {
                        type: "button",
                        id: "ns-t3ai-generate",
                        label: editor.lang.nst3ai_content.generate,
                        onClick: function (element) {
                            let promptElement = this.getDialog().getContentElement('ns-tab-basic', 'ns-t3ai-prompt');
                            let promptValue = promptElement && promptElement.getValue().trim();
                            if (!promptValue) {
                                alert(editor.lang.nst3ai_content.errorNotEmpty);
                            } else {
                                select_temperature = parseFloat(this.getDialog().getValueOf("tab-advanced", "temperature"));
                                select_amount = parseInt(this.getDialog().getValueOf("tab-advanced", "amount"));
                                let resElement = this.getDialog().getContentElement('ns-tab-basic', 'ns-t3ai-generated-content');
                                var buttonElement = this.getDialog().getContentElement('ns-tab-basic', 'ns-t3ai-generate');
                                buttonElement.disable()
                                const NsT3AiKey = TYPO3.settings.NS_T3AI_KEY;
                                var xhr = new XMLHttpRequest();
                                xhr.open("POST", "https://api.openai.com/v1/completions", true);
                                xhr.setRequestHeader("Content-Type", "application/json");
                                xhr.setRequestHeader("Authorization", "Bearer " + NsT3AiKey);
                                resElement.setValue('Loading...')
                                xhr.send(JSON.stringify({
                                    prompt: promptValue,
                                    // Text to complete
                                    max_tokens: select_max_tokens,
                                    // 1 to 4000
                                    model: select_model,
                                    // 'text-davinci-003', 'text-curie-001', 'text-babbage-001', 'text-ada-001'
                                    temperature: select_temperature,
                                    // 0.0 is equivalent to greedy sampling
                                    top_p: 1,
                                    // 1.0 is equivalent to greedy sampling
                                    n: select_amount,
                                    // Number of results to return
                                    frequency_penalty: 0,
                                    // 0.0 is equivalent to no penalty
                                    presence_penalty: 0
                                    // 0.0 is equivalent to no penalty
                                }));
                                xhr.onreadystatechange = function() {
                                    if (this.readyState === 4) {
                                        if (this.status === 200) {
                                            let completeText = "", choices = JSON.parse(this.responseText).choices;
                                            for (let i = 0; i < choices.length; i++) {
                                                completeText += "<p>" + escapeHtml(choices[i].text) + "</p>";
                                            }
                                            resElement.setValue(completeText.trim().replace(/\n\s+/g, '\n'));
                                            buttonElement.enable()
                                        } else {
                                            resElement.setValue(" Error: " + this.responseText);
                                            buttonElement.enable()
                                        }
                                    }
                                };
                                xhr.onerror = function() {
                                    resElement.setValue(" Error: " + this.responseText);
                                    buttonElement.enable()
                                };
                            }
                        }
                    },
                    {
                        type: "textarea",
                        id: "ns-t3ai-generated-content",
                        rows: 5,
                        validate: CKEDITOR.dialog.validate.notEmpty(editor.lang.nst3ai_content.errorNotEmpty),
                    }
                ]
            },
            {
                id: "tab-advanced",
                label: editor.lang.nst3ai_content.tabAdvanced,
                elements: [
                    // Add select field with different temperatures from 0 to 2
                    {
                        type: "select",
                        id: "temperature",
                        title: editor.lang.nst3ai_content.temperature,
                        label: editor.lang.nst3ai_content.temperatureLabel,
                        default: 0.5,
                        items: [
                            ["0.0", 0.01],
                            ["0.25", 0.25],
                            ["0.5", 0.5],
                            ["0.75", 0.75],
                            ["1.0", 1],
                            ["1.25", 1.25],
                            ["1.5", 1.5],
                            ["1.75", 1.75],
                            ["2.0", 2]
                        ],
                        setup: function(element) {
                            element.setAttribute("type", "number");
                            this.setValue(element.getText());
                        },
                        commit: function(element) {
                        }
                    },
                    // Add select field for number of results
                    {
                        type: "select",
                        id: "amount",
                        title: editor.lang.nst3ai_content.amount,
                        label: editor.lang.nst3ai_content.amountLabel,
                        default: 1,
                        items: [
                            ["1", 1],
                            ["2", 2],
                            ["3", 3],
                            ["4", 4]
                        ],
                        setup: function(element) {
                            element.setAttribute("type", "number");
                            this.setValue(element.getText());
                        },
                        commit: function(element) {
                        }
                    }
                ]
            }
        ],
        onOk: function() {
            let dialog = this, nst3ai = editor.document.createElement("div");
            select_temperature = parseFloat(dialog.getValueOf("tab-advanced", "temperature"));
            select_amount = parseInt(dialog.getValueOf("tab-advanced", "amount"));
            select_max_tokens = 4e3;
            dialog.commitContent(nst3ai);
            editor.insertElement(nst3ai);
        }
    };
});
CKEDITOR.plugins.add("nst3ai_content", {
    icons: "ns-t3ai",
    lang: ["en", "de"],
    init: function(editor) {
        editor.addCommand("nst3ai_content", new CKEDITOR.dialogCommand("nsT3AiContentDialog"));
        editor.ui.addButton("ns_t3ai", {
            label: "T3AI Content Assistance",
            command: "nst3ai_content",
            toolbar: "insert",
            icon: this.path + "icons/ns-copywriter.png"
        });
    }
});
CKEDITOR.config.keystrokes = [
    [CKEDITOR.ALT + 67, "nst3ai_content"]
];
