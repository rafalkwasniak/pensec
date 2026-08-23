{{--
    Both icons are in the markup and CSS hides the one that does not apply, so the
    button is right on the first paint. Each shows the theme the click leads to.
--}}
<button type="button" data-theme-toggle
        class="rounded-lg border border-ink-line p-2 text-muted transition hover:border-brand hover:text-chrome"
        title="Przełącz motyw jasny i ciemny" aria-label="Przełącz motyw jasny i ciemny">
    <svg class="theme-when-dark h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="4.2"/>
        <path d="M12 2v2.2M12 19.8V22M22 12h-2.2M4.2 12H2M18.4 5.6l-1.6 1.6M7.2 16.8l-1.6 1.6M18.4 18.4l-1.6-1.6M7.2 7.2 5.6 5.6"/>
    </svg>
    <svg class="theme-when-light h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <path d="M21 12.9A9 9 0 1 1 11.1 3a7 7 0 0 0 9.9 9.9z"/>
    </svg>
</button>
