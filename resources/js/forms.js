// Browsers may restore a submitted form from their back/forward cache.
const submittedButtons = new Map();
document.addEventListener('submit', event => {
    for (const button of event.target.querySelectorAll('button[type="submit"],input[type="submit"]')) {
        if (!submittedButtons.has(button)) submittedButtons.set(button, { disabled: button.disabled, html: button.innerHTML, value: button.value });
    }
}, true);
window.addEventListener('pageshow', event => {
    if (!event.persisted) return;
    for (const [button, original] of submittedButtons) {
        button.disabled = original.disabled;
        if (button.tagName === 'INPUT') button.value = original.value;
        else button.innerHTML = original.html;
    }
    submittedButtons.clear();
});
