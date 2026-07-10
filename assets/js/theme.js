(() => {
  const key = 'Memora-theme';
  const root = document.documentElement;
  const applyTheme = (theme) => {
    root.dataset.theme = theme;
    localStorage.setItem(key, theme);
    document.querySelectorAll('.theme-toggle').forEach((button) => {
      button.setAttribute('aria-pressed', String(theme === 'dark'));
      button.textContent = theme === 'dark' ? 'Mode clair' : 'Mode sombre';
    });
  };
  document.addEventListener('DOMContentLoaded', () => {
    applyTheme(localStorage.getItem(key) || root.dataset.theme || 'light');
    document.querySelectorAll('.theme-toggle').forEach((button) => {
      button.addEventListener('click', () => applyTheme(root.dataset.theme === 'dark' ? 'light' : 'dark'));
    });
  });
})();
