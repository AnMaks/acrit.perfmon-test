(function(window){
    function decodeBase64Unicode(value) {
        var binary;
        var bytes;
        var index;

        try {
            binary = window.atob(value);
            bytes = [];
            for (index = 0; index < binary.length; index++) {
                bytes.push('%' + ('00' + binary.charCodeAt(index).toString(16)).slice(-2));
            }
            return decodeURIComponent(bytes.join(''));
        } catch (e) {
            try {
                return window.atob(value);
            } catch (innerError) {
                return '';
            }
        }
    }

    function parseConfig(node) {
        var encoded = node ? node.getAttribute('data-config') : '';
        if (!encoded) {
            return {};
        }
        try {
            return JSON.parse(decodeBase64Unicode(encoded));
        } catch (e) {
            return {};
        }
    }

    function escapeHtml(value) {
        return BX.util.htmlspecialchars(String(value || ''));
    }

    window.acritPerfmonShowHint = function(contentId, bindElement) {
        var contentNode = BX(contentId);
        if (!contentNode) {
            return;
        }

        var popup = BX.PopupWindowManager.create('acrit_perfmon_popup_' + contentId, bindElement, {
            autoHide: true,
            closeByEsc: true,
            offsetTop: 0,
            offsetLeft: 18,
            lightShadow: true,
            closeIcon: true,
            className: 'acrit-perfmon-hint-window',
            content: contentNode.innerHTML,
            zIndex: 2000
        });

        popup.show();
    };

    BX.ready(function(){
        var appNode = BX('acrit-perfmon-app');
        if (!appNode) {
            return;
        }

        var ui = parseConfig(appNode);
        var state = {
            isRunning: false,
            stopRequested: false,
            tests: [],
            results: [],
            progress: 0,
            progressTimer: null
        };

        var nodes = {
            runButton: BX('acrit-perfmon-run-button'),
            stopButton: BX('acrit-perfmon-stop-button'),
            progressFill: BX('acrit-perfmon-progress-fill'),
            progressPercent: BX('acrit-perfmon-progress-percent'),
            progressState: BX('acrit-perfmon-progress-state'),
            summaryChecked: BX('acrit-perfmon-summary-checked'),
            summarySuccess: BX('acrit-perfmon-summary-success'),
            summaryFail: BX('acrit-perfmon-summary-fail'),
            resultsWrap: BX('acrit-perfmon-results'),
            resultsBody: BX('acrit-perfmon-results-body'),
            auditWrap: BX('acrit-perfmon-audit-wrap')
        };

        function sortResults(items) {
            return items.slice().sort(function(a, b){
                var orderA = ui.groupOrder && ui.groupOrder[a.GROUP] ? ui.groupOrder[a.GROUP] : 9999;
                var orderB = ui.groupOrder && ui.groupOrder[b.GROUP] ? ui.groupOrder[b.GROUP] : 9999;
                if (orderA !== orderB) {
                    return orderA - orderB;
                }
                var sortA = parseInt(a.SORT || 0, 10);
                var sortB = parseInt(b.SORT || 0, 10);
                if (sortA !== sortB) {
                    return sortA - sortB;
                }
                return String(a.TITLE || '').localeCompare(String(b.TITLE || ''));
            });
        }

        function setProgress(percent, text) {
            percent = Math.max(0, Math.min(100, Math.round(percent)));
            state.progress = percent;
            if (nodes.progressFill) {
                nodes.progressFill.style.width = percent + '%';
            }
            if (nodes.progressPercent) {
                nodes.progressPercent.innerHTML = percent + '%';
            }
            if (nodes.progressState && typeof text !== 'undefined') {
                nodes.progressState.innerHTML = escapeHtml(text);
            }
        }

        function animatePendingProgress(targetPercent) {
            clearInterval(state.progressTimer);
            state.progressTimer = setInterval(function(){
                if (!state.isRunning) {
                    clearInterval(state.progressTimer);
                    return;
                }
                if (state.progress >= targetPercent) {
                    return;
                }
                setProgress(state.progress + 1);
            }, 90);
        }

        function stopProgressAnimation() {
            clearInterval(state.progressTimer);
            state.progressTimer = null;
        }

        function setRunningMode(isRunning) {
            state.isRunning = isRunning;
            if (nodes.runButton) {
                nodes.runButton.disabled = isRunning;
                if (isRunning) {
                    BX.addClass(nodes.runButton, 'adm-btn-disabled');
                } else {
                    BX.removeClass(nodes.runButton, 'adm-btn-disabled');
                }
            }
            if (nodes.stopButton) {
                nodes.stopButton.disabled = !isRunning;
            }
        }

        function updateSummary() {
            var total = state.results.length;
            var success = 0;
            var fail = 0;

            state.results.forEach(function(item){
                if (item.SUCCESS) {
                    success++;
                } else {
                    fail++;
                }
            });

            if (nodes.summaryChecked) {
                nodes.summaryChecked.innerHTML = String(total);
            }
            if (nodes.summarySuccess) {
                nodes.summarySuccess.innerHTML = String(success);
            }
            if (nodes.summaryFail) {
                nodes.summaryFail.innerHTML = String(fail);
            }
        }

        function buildHintHtml(item) {
            var html = '';
            html += '<div class="acrit-perfmon-hint-popup">';
            html += '<div class="acrit-perfmon-hint-popup__title">' + escapeHtml(item.TITLE) + '</div>';
            html += '<div class="acrit-perfmon-hint-popup__text">' + escapeHtml(item.DESCRIPTION) + '</div>';
            if (item.MESSAGE) {
                html += '<div class="acrit-perfmon-hint-popup__row"><strong>' + escapeHtml(ui.messages.result) + ':</strong> ' + escapeHtml(item.MESSAGE) + '</div>';
            }
            html += '<div class="acrit-perfmon-hint-popup__row"><strong>' + escapeHtml(ui.messages.recommendation) + ':</strong> ' + escapeHtml(item.RECOMMENDATION) + '</div>';
            html += '</div>';
            return html;
        }

        function renderResults() {
            if (!nodes.resultsBody || !nodes.resultsWrap) {
                return;
            }

            if (!state.results.length) {
                nodes.resultsWrap.className = 'acrit-perfmon-results is-hidden';
                nodes.resultsBody.innerHTML = '';
                if (nodes.auditWrap) {
                    nodes.auditWrap.className = 'acrit-perfmon-system-check__footer is-hidden';
                }
                return;
            }

            var html = '';
            var sorted = sortResults(state.results);
            var currentGroup = '';

            sorted.forEach(function(item, index){
                if (item.GROUP !== currentGroup) {
                    currentGroup = item.GROUP;
                    html += '<tr class="acrit-perfmon-result-table__group-row"><td colspan="3">' + escapeHtml(currentGroup) + '</td></tr>';
                }

                var isSuccess = !!item.SUCCESS;
                var statusText = item.MESSAGE || item.STATUS_TEXT || (isSuccess ? ui.messages.statusOk : ui.messages.statusFail);
                var hintId = 'acrit_perfmon_hint_' + index;

                html += '<tr>';
                html += '<td class="acrit-perfmon-result-table__title-cell">' + escapeHtml(item.TITLE) + '</td>';
                html += '<td class="acrit-perfmon-result-table__status-cell">';
                html += '<span class="acrit-perfmon-result-table__status-wrap ' + (isSuccess ? 'is-success' : 'is-fail') + '">';
                html += '<span class="acrit-perfmon-result-table__status-icon"></span>';
                html += '<span class="acrit-perfmon-result-table__status-text">' + escapeHtml(statusText) + '</span>';
                html += '</span>';
                html += '</td>';
                html += '<td class="acrit-perfmon-result-table__help-cell">';
                html += '<a href="javascript:void(0)" class="acrit-perfmon-help-link" onclick="acritPerfmonShowHint(\'' + hintId + '\', this); return false;">?</a>';
                html += '<div id="' + hintId + '" class="acrit-perfmon-hint-source">' + buildHintHtml(item) + '</div>';
                html += '</td>';
                html += '</tr>';
            });

            nodes.resultsWrap.className = 'acrit-perfmon-results';
            nodes.resultsBody.innerHTML = html;
            if (nodes.auditWrap) {
                nodes.auditWrap.className = 'acrit-perfmon-system-check__footer';
            }
        }

        function ajaxRequest(data, onSuccess, onFailure) {
            BX.ajax({
                url: ui.ajaxUrl,
                method: 'POST',
                dataType: 'json',
                data: data,
                onsuccess: function(response){
                    if (response && response.success) {
                        if (onSuccess) {
                            onSuccess(response);
                        }
                        return;
                    }
                    if (onFailure) {
                        onFailure(response && response.error ? response.error : ui.messages.ajaxError);
                    }
                },
                onfailure: function(){
                    if (onFailure) {
                        onFailure(ui.messages.ajaxError);
                    }
                }
            });
        }

        function finishRun(isStopped) {
            stopProgressAnimation();

            if (!state.results.length) {
                setRunningMode(false);
                setProgress(0, ui.messages.noTests);
                renderResults();
                updateSummary();
                return;
            }

            setProgress(100, isStopped ? ui.messages.stopped : ui.messages.finalizing);

            ajaxRequest({
                sessid: ui.sessid,
                action: 'finalize_run',
                results: JSON.stringify(state.results)
            }, function(){
                updateSummary();
                renderResults();
                setRunningMode(false);
                setProgress(100, isStopped ? ui.messages.stopped : ui.messages.done);
            }, function(errorText){
                setRunningMode(false);
                setProgress(100, errorText || ui.messages.ajaxError);
            });
        }

        function runNext(index) {
            if (state.stopRequested) {
                finishRun(true);
                return;
            }

            if (index >= state.tests.length) {
                finishRun(false);
                return;
            }

            var current = state.tests[index];
            var currentPercent = Math.max(4, Math.round((index / Math.max(state.tests.length, 1)) * 100));
            setProgress(currentPercent, ui.messages.running.replace('#TITLE#', current.TITLE));
            animatePendingProgress(Math.min(95, currentPercent + 7));

            ajaxRequest({
                sessid: ui.sessid,
                action: 'run_single_test',
                code: current.CODE
            }, function(response){
                stopProgressAnimation();
                state.results.push(response.result);
                updateSummary();
                renderResults();
                var donePercent = Math.round((state.results.length / Math.max(state.tests.length, 1)) * 100);
                setProgress(donePercent, ui.messages.running.replace('#TITLE#', current.TITLE));
                runNext(index + 1);
            }, function(errorText){
                stopProgressAnimation();
                state.results.push({
                    CODE: current.CODE,
                    TITLE: current.TITLE,
                    GROUP: current.GROUP,
                    SUCCESS: false,
                    STATUS_TEXT: ui.messages.statusFail,
                    MESSAGE: errorText || ui.messages.ajaxError,
                    DESCRIPTION: current.DESCRIPTION || '',
                    RECOMMENDATION: current.RECOMMENDATION || '',
                    SORT: current.SORT || 0
                });
                updateSummary();
                renderResults();
                runNext(index + 1);
            });
        }

        function startRun() {
            if (state.isRunning) {
                return;
            }

            state.stopRequested = false;
            state.tests = [];
            state.results = [];
            updateSummary();
            renderResults();
            setRunningMode(true);
            setProgress(2, ui.messages.preparing);
            animatePendingProgress(12);

            ajaxRequest({
                sessid: ui.sessid,
                action: 'get_tests_list'
            }, function(response){
                stopProgressAnimation();
                state.tests = response.tests || [];
                if (!state.tests.length) {
                    setRunningMode(false);
                    setProgress(0, ui.messages.noTests);
                    return;
                }
                runNext(0);
            }, function(errorText){
                stopProgressAnimation();
                setRunningMode(false);
                setProgress(0, errorText || ui.messages.ajaxError);
            });
        }

        function stopRun() {
            if (!state.isRunning) {
                return;
            }
            state.stopRequested = true;
            setProgress(state.progress, ui.messages.stopped);
        }

        if (nodes.runButton) {
            BX.bind(nodes.runButton, 'click', startRun);
        }
        if (nodes.stopButton) {
            BX.bind(nodes.stopButton, 'click', stopRun);
        }

        updateSummary();
        renderResults();
        setProgress(0, ui.messages.idle);
    });
})(window);