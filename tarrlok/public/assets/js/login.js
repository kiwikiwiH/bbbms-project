document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-toggle-password]').forEach((toggle) => {
    const wrap = toggle.closest('.login-input-wrap, .reg-input-wrap');
    const passwordInput = wrap?.querySelector('input');

    if (!passwordInput) {
      return;
    }

    toggle.addEventListener('click', () => {
      const isHidden = passwordInput.type === 'password';
      passwordInput.type = isHidden ? 'text' : 'password';
      toggle.querySelector('.material-symbols-outlined').textContent = isHidden
        ? 'visibility'
        : 'visibility_off';
    });
  });
});
