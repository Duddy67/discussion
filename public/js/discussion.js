document.addEventListener('DOMContentLoaded', () => {

    let registerBtn = document.getElementById('register');
    let unregisterBtn = document.getElementById('unregister');
    let registerWaitingListBtn = document.getElementById('registerWaitingList');
    let unregisterWaitingListBtn = document.getElementById('unregisterWaitingList');
    let cancel = document.getElementById('cancel');
    let deleteBtn = document.getElementById('delete');

    if (registerBtn !== null) {
        registerBtn.onclick = function(e) { 
            alert('REGISTER '+registerBtn.dataset.url);
            window.location = registerBtn.dataset.url;
        }
    }

    if (unregisterBtn !== null) {
        unregisterBtn.onclick = function(e) { 
            alert('UNREGISTER '+unregisterBtn.dataset.url);
            window.location = unregisterBtn.dataset.url;
        }
    }

    if (registerWaitingListBtn !== null) {
        registerWaitingListBtn.onclick = function(e) { 
            alert('REGISTER WAITING LIST'+registerWaitingListBtn.dataset.url);
            window.location = registerWaitingListBtn.dataset.url;
        }
    }

    if (unregisterWaitingListBtn !== null) {
        unregisterWaitingListBtn.onclick = function(e) { 
            alert('UNREGISTER WAITING LIST'+unregisterWaitingListBtn.dataset.url);
            window.location = unregisterWaitingListBtn.dataset.url;
        }
    }

    if (cancel !== null) {
        cancel.onclick = function(e) { 
            alert('CANCEL '+cancel.dataset.url);
            window.location = cancel.dataset.url;
        }
    }

    if (deleteBtn !== null) {
        deleteBtn.onclick = function(e) { 
            alert('DELETE');
            document.getElementById('deleteItem').submit();
        }
    }

    if (document.getElementById('submit')) {
        document.getElementById('submit').onclick = function(e) { 
            const spinner = document.getElementById('ajax-progress');
            spinner.classList.remove('d-none');

            let formData = new FormData(document.getElementById('form'));

            let ajax = new C_Ajax.init({
                method: 'post',
                url: document.getElementById('form').action,
                dataType: 'json',
                data: formData,
                headers: {'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json'}
            });

            ajax.run(getAjaxResult);
        }
    }

    function getAjaxResult(status, result) {
        const spinner = document.getElementById('ajax-progress');
        spinner.classList.add('d-none');

        if (status === 200) {
            // Loop through the returned result.
            for (const [key, value] of Object.entries(result)) {
                if (key == 'redirect') {
                    window.location.href = result.redirect;
                }
                else if (key == 'refresh') {
                    refreshFieldValues(result.refresh);
                }
                // messages
                else if (['success', 'warning', 'info'].includes(key)) {
                    displayMessage(key, value);
                }
            }
        }
        else if (status === 422) {
            displayMessage('danger', 'Please check the form below for errors.');
            // Loop through the returned errors and set the messages accordingly.
            for (const [name, message] of Object.entries(result.errors)) {
                document.getElementById(name+'Error').innerHTML = message;
            }
        }
        else {
            displayMessage('danger', 'Error '+status+': '+result.message);
        }
    }

    function displayMessage(type, message) {
        // Empty some possible error messages.
        document.querySelectorAll('div[id$="Error"]').forEach(elem => {
            elem.innerHTML = '';
        });

        // Hide the possible displayed flash messages.
        document.querySelectorAll('.flash-message').forEach(elem => {
            if (!elem.classList.contains('d-none')) {
                elem.classList.add('d-none');
            }
        });

        // Adapt to Bootstrap alert class names.
        type = (type == 'error') ? 'danger' : type;

        const messageAlert = document.getElementById('ajax-message-alert');
        messageAlert.classList.remove('d-none', 'alert-success', 'alert-danger', 'alert-warning', 'alert-info');
        messageAlert.classList.add('alert-'+type);
        document.getElementById('ajax-message').innerHTML = message;
    }
});
