/**
 * Listen to value changes into the setup wizard
 * and toggle steps when needed.
 */
window.addEventListener('barn2_setup_wizard_changed', (dispatchedEvent) => {

    const frontend = dispatchedEvent.detail.frontend === '1' || dispatchedEvent.detail.frontend === true;
    const backend = dispatchedEvent.detail.backend === '1' || dispatchedEvent.detail.backend === true;

    const showStep = dispatchedEvent.detail.showStep
    const hideStep = dispatchedEvent.detail.hideStep

    if (frontend === true) {
        showStep('general-settings')
        showStep('images')
    } else {
        hideStep('general-settings')
        hideStep('images')
    }

}, false);