define(["TYPO3/CMS/Core/Ajax/AjaxRequest",
    "TYPO3/CMS/Backend/Notification",
    "TYPO3/CMS/Backend/Icons",
], function (AjaxRequest, Notification, Icons) {
    addEventListener();

    function addEventListener() {
        document.querySelectorAll('.ns-t3ai-seo-generation-btn').forEach(function (button) {
            button.addEventListener("click", function (ev) {
                ev.preventDefault();
                if (button !== null) {
                    button.disabled = true;

                    Icons.getIcon('spinner-circle-light', Icons.sizes.large).then(function (markup) {
                        document.getElementById('ns-t3ai__loader').innerHTML = markup;
                        document.getElementById('ns-t3ai__loader').classList.add('ns-show-overlay');
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
        Notification.info(TYPO3.lang['NsT3Ai.request.send'], TYPO3.lang['NsT3Ai.generating'], 8);
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
                    Notification.error(TYPO3.lang['NsT3Ai.error'], responseBody.error);
                } else {
                    handleResponse(pageId, fieldName, responseBody)
                    Notification.success(TYPO3.lang['NsT3Ai.generated.success'], TYPO3.lang['NsT3Ai.generated.success.message'], 8);
                    button.disabled = false;
                    // button.querySelector('.btn-label').innerHTML = TYPO3.lang['NsT3Ai.regenerate'];
                    document.getElementById('ns-t3ai__loader').innerHTML = '';
                    document.getElementById('ns-t3ai__loader').classList.remove('ns-show-overlay');
                }
            })
            .catch((error) => {
                Notification.error(TYPO3.lang['NsT3Ai.error'], error);
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
        document.querySelector('.ns-t3ai-seo-set-btn').style.display = 'block';

        document.querySelectorAll('.ns-t3ai-seo-set-btn').forEach(function (button) {
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
        Notification.info(TYPO3.lang['NsT3Ai.request.send'], TYPO3.lang['NsT3Ai.generating'], 50);
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
                    Notification.error(TYPO3.lang['NsT3Ai.error'], responseBody.error);
                } else {
                    Notification.success(TYPO3.lang['NsT3Ai.generated.success'], TYPO3.lang['NsT3Ai.generated.success.message'], 8);
                    document.querySelector('.ns-t3ai__seo').style.display = 'none';
                    location.reload();
                    document.querySelector('.ns-t3ai-seo-set-btn').style.display = 'none';
                }
            })
            .catch((error) => {
                Notification.error(TYPO3.lang['NsT3Ai.error'], error);
            });
    }
// create CE from selected outlines - end
});

// Top 2 Tabs Button changes Code here
const tabButtons = document.querySelectorAll('.ns-t3ai__btn-top');
function toggleTab(tabId) {
    const content = document.getElementById(tabId);
    const activeButton = document.querySelector(`[ns-data-target="${tabId}"]`);

    if (content.classList.contains('active-tab')) {
        content.classList.remove('active-tab');
        activeButton.classList.remove('active');
    } else {
        const contentSections = document.querySelectorAll('.ns-t3ai--content');
        contentSections.forEach((section) => {
            section.classList.remove('active-tab');
        });
        content.classList.add('active-tab');
        activeButton.classList.add('active');

        const tabButtons = document.querySelectorAll('.ns-t3ai__btn-top');
        tabButtons.forEach((button) => {
            if (button !== activeButton) {
                button.classList.remove('active');
            }
        });
    }
}

tabButtons.forEach((button) => {
    button.addEventListener('click', function () {
        const tabContentId = button.getAttribute('ns-data-target');
        toggleTab(tabContentId);
    });
});
// Top 2 Tabs Button changes Code here
