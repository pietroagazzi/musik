// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';

// start the Stimulus application
// import './bootstrap';

// JQuery
import $ from 'jquery';

// Toastify
import ToastifyEs from "toastify-js/src/toastify-es";


for (let type in alerts) {
    for (let i = 0; i < alerts[type].length; i++) {
        ToastifyEs({
            text: alerts[type][i],
            className: `alert alert-${type}`,
            duration: 10_000,
            gravity: 'bottom',
            position: 'right',
            stopOnFocus: true,
            // close: true
        }).showToast()
    }
}