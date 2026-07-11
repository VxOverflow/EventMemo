(() => {
  // Centralise le choix du thème et le conserve dans le navigateur.
  const key = 'Memora-theme';
  const root = document.documentElement;
  // Met à jour le document et tous les boutons de basculement visibles.
  const applyTheme = (theme) => {
    root.dataset.theme = theme;
    localStorage.setItem(key, theme);
    document.querySelectorAll('.theme-toggle').forEach((button) => {
      button.setAttribute('aria-pressed', String(theme === 'dark'));
      button.textContent = theme === 'dark' ? 'Mode clair' : 'Mode sombre';
    });
  };
  // Initialise le thème sauvegardé, puis écoute les interactions utilisateur.
  document.addEventListener('DOMContentLoaded', () => {
    applyTheme(localStorage.getItem(key) || root.dataset.theme || 'light');
    document.querySelectorAll('.theme-toggle').forEach((button) => {
      button.addEventListener('click', () => applyTheme(root.dataset.theme === 'dark' ? 'light' : 'dark'));
    });
  });
})();
