define(["TYPO3/CMS/Core/Ajax/AjaxRequest",
    "TYPO3/CMS/Backend/Notification",
    "TYPO3/CMS/Backend/Icons",
], function (AjaxRequest, Notification, Icons) {
    addEventListener();

    function addEventListener() {
        document.querySelectorAll('.ns-openai-seo-generation-btn').forEach(function (button) {
            button.addEventListener("click", function (ev) {
                ev.preventDefault();
                if (button !== null) {
                    button.disabled = true;

                    Icons.getIcon('spinner-circle-light', Icons.sizes.large).then(function (markup) {
                        document.getElementById('ns-openai__loader').innerHTML = markup;
                        document.getElementById('ns-openai__loader').classList.add('ns-show-overlay');
                    });
                }
                let pageId = parseInt(this.getAttribute('data-page-id'));
                let fieldName = this.getAttribute('data-field-name');
                sendAjaxRequest(pageId, fieldName, button);
            });
        });
    }

    /**
     *
     * @param {int} pageId
     * @param {string} fieldName
     * @param {object} button
     */
    function sendAjaxRequest(pageId, fieldName, button) {
        Notification.info('start', 'keywords', 8);
        var keyword = '';
        if (document.getElementById('topic_keyword')) {
            keyword = document.getElementById('topic_keyword').value
        }
        new AjaxRequest(TYPO3.settings.ajaxUrls[fieldName + '_generation'])
            .post(
                {
                    pageId: pageId,
                    prompt: document.getElementById(fieldName + '_prompt').value,
                    fieldName: fieldName,
                    topicKeyword: keyword
                }
            )
            .then(async function (response) {
                const resolved = await response.resolve();
                const responseBody = JSON.parse(resolved);
                if (responseBody.error) {
                    Notification.error('error', responseBody.error);
                } else {
                    handleResponse(pageId, fieldName, responseBody)
                    Notification.success('finish', 'success', 8);
                    button.disabled = false;
                    // button.querySelector('.btn-label').innerHTML = TYPO3.lang['NsOpenai.regenerate'];
                    document.getElementById('ns-openai__loader').innerHTML = '';
                    document.getElementById('ns-openai__loader').classList.remove('ns-show-overlay');
                }
            })
            .catch((error) => {
                Notification.error('something went wrong', error);
            });
    }

    /**
     *
     * @param {int} pageId
     * @param {string} fieldName
     * @param {object} responseBody
     */
    function handleResponse(pageId, fieldName, responseBody) {
        document.querySelector('#nav_' + fieldName).innerHTML = responseBody.output;
        document.querySelector('.ns-openai-seo-set-btn').style.display = 'block';

        document.querySelectorAll('.ns-openai-seo-set-btn').forEach(function (button) {
            button.addEventListener("click", function (ev) {
                ev.preventDefault();
                let pageId = parseInt(this.getAttribute('data-page-id'));
                let fieldName = this.getAttribute('data-field-name');
                let selectedText;
                const ele = document.getElementsByName('generatedseo_titleSuggestions');
                for (let i = 0; i < ele.length; i++) {
                    if (ele[i].type === "radio") {
                        if (ele[i].checked){
                            selectedText =  ele[i].value;
                        }
                    }
                }
                sendSaveRequest(pageId, fieldName,selectedText);
            });
        });
    }
    /**
     *
     * @param {int} pageId
     * @param {string} fieldName
     * @param {string} suggestion
     */
    function sendSaveRequest(pageId, fieldName, suggestion) {
        Notification.info('start', 'keywords', 8);
        new AjaxRequest(TYPO3.settings.ajaxUrls['save_request'])
            .post(
                {
                    pageId: pageId,
                    fieldName: fieldName,
                    suggestion: suggestion ,
                }
            )
            .then(async function (response) {
                const resolved = await response.resolve();
                const responseBody = JSON.parse(resolved);
                if (responseBody.error) {
                    Notification.error('error', responseBody.error);
                } else {
                    // handleResponse(pageId, fieldName, responseBody)
                    Notification.success('finish', 'success', 8);
                    document.querySelector('.ns-openai__seo').style.display = 'none';
                    location.reload();
                    document.querySelector('.ns-openai-seo-set-btn').style.display = 'none';
                }
            })
            .catch((error) => {
                Notification.error('something went wrong', error);
            });
    }
// create CE from selected outlines - end
});

// Start Tabs changes Code here
const nsOpenai = document.querySelectorAll('.ns-openai');
if (nsOpenai.length > 0) {
    const getSeoBtn = document.querySelector('.ns-openai__btn-seo');
    if(getSeoBtn){
        getSeoBtn.addEventListener('click', () => {
            getSeoBtn.classList.add('active');
            getSeoTab.style.display = 'block';
        });
    }
}
// End Tabs changes Code here
