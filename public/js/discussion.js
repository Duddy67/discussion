document.addEventListener('DOMContentLoaded', () => {

    let registerBtn = document.getElementById('register');
    if (registerBtn !== null) {
        registerBtn.onclick = function(e) { 
            alert('REGISTER '+registerBtn.dataset.url);
            window.location = registerBtn.dataset.url;
        }
    }

});
