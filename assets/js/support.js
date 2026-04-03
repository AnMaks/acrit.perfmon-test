BX.ready(function () {
    var textarea = BX('acrit-perfmon-support-message');
    var button = BX('acrit-perfmon-send-button');
    var note = BX('acrit-perfmon-autofill-note');
    var form = BX('form-ticket');

    if (textarea && button && textarea.getAttribute('data-autofilled') === 'Y') {
        setTimeout(function () {
            try {
                textarea.focus();
                if (typeof textarea.scrollIntoView === 'function') {
                    textarea.scrollIntoView({behavior: 'smooth', block: 'center'});
                }
            } catch (e) {
            }
            BX.addClass(button, 'acrit-perfmon-pulse');
            if (note) {
                BX.addClass(note, 'acrit-perfmon-autofill-note--visible');
            }
        }, 250);
    }

    if (!button || !textarea || !form) {
        return;
    }

    BX.bind(button, 'click', function (event) {
        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }

        var textMessage = BX.util.trim(textarea.value || '');
        var errorMessage = textarea.getAttribute('data-error') || '';
        if (!textMessage.length) {
            alert(errorMessage);
            return;
        }

        var chunks = [textMessage, '\n\n'];
        var metaFields = form.querySelectorAll('[data-ticket-meta="Y"]');
        Array.prototype.forEach.call(metaFields, function (field) {
            var label = field.getAttribute('data-label') || '';
            var value = field.value || '';
            chunks.push(label + ': ' + value + '\n');
        });

        var textField = form.querySelector('input[name="ticket_text"]');
        if (textField) {
            textField.value = chunks.join('');
        }

        form.submit();
    });
});