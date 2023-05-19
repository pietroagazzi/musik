import ToastifyEs from "toastify-js/src/toastify-es";

function renderAlerts(alerts) {
    for (let type in alerts) {
        for (let i = 0; i < alerts[type].length; i++) {
            ToastifyEs({
                text: alerts[type][i],
                className: `alert alert-${type}`,
                duration: 10_000,
                gravity: 'bottom',
                position: 'right',
                stopOnFocus: true,
            }).showToast()
        }
    }
}

export default renderAlerts;