{{--
    Belongs in <head>, before the stylesheet. It runs synchronously so the stored
    choice is on <html> before the first paint - otherwise the page would flash the
    default theme. With no choice on file the system preference decides; with no
    JavaScript at all the `data-theme` already in the markup stands.

    The click handler is registered here too, delegated from the document, so a page
    may carry as many toggles as it likes and none of them needs its own listener.
--}}
<script>
    (function () {
        var storageKey = 'pensec.theme';
        var root = document.documentElement;

        function remembered() {
            try {
                var stored = window.localStorage.getItem(storageKey);

                return stored === 'light' || stored === 'dark' ? stored : null;
            } catch (error) {
                return null;
            }
        }

        function preferred() {
            return window.matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
        }

        root.dataset.theme = remembered() ?? preferred();

        document.addEventListener('click', function (event) {
            if (! (event.target instanceof Element) || ! event.target.closest('[data-theme-toggle]')) {
                return;
            }

            root.dataset.theme = root.dataset.theme === 'light' ? 'dark' : 'light';

            try {
                window.localStorage.setItem(storageKey, root.dataset.theme);
            } catch (error) {
                // A browser refusing storage still gets the switch, just not the memory.
            }
        });
    })();
</script>
