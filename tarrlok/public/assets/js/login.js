document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-toggle-password]').forEach((toggle) => {
    if (toggle.dataset.bound === '1') {
      return;
    }
    toggle.dataset.bound = '1';

    const wrap = toggle.closest('.login-input-wrap, .reg-input-wrap');
    const passwordInput = wrap?.querySelector('input[type="password"], input[type="text"]');
    const icon = toggle.querySelector('.material-symbols-outlined');

    if (!passwordInput || !icon) {
      return;
    }

    toggle.addEventListener('click', () => {
      const show = passwordInput.type === 'password';
      passwordInput.type = show ? 'text' : 'password';
      icon.textContent = show ? 'visibility' : 'visibility_off';
      toggle.setAttribute(
        'aria-label',
        show ? 'Hide password' : 'Show password'
      );
    });
  });
});
