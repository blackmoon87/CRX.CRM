/**
 * CRX Global Command Palette & Keyboard Shortcuts Controller
 * Pure Vanilla JS — Linear / Attio Style Omnibox
 */

document.addEventListener('DOMContentLoaded', () => {
    initCommandPalette();
});

function initCommandPalette() {
    const overlay = document.getElementById('commandPaletteOverlay');
    const input = document.getElementById('commandPaletteInput');
    const resultsContainer = document.getElementById('commandPaletteResults');
    const helpOverlay = document.getElementById('shortcutsHelpOverlay');

    let activeIndex = 0;

    // Toggle Palette
    window.toggleCommandPalette = function() {
        if (!overlay) return;
        if (overlay.style.display === 'flex') {
            closeCommandPalette();
        } else {
            openCommandPalette();
        }
    };

    function openCommandPalette() {
        if (!overlay) return;
        overlay.style.display = 'flex';
        input.value = '';
        filterItems('');
        input.focus();
        activeIndex = 0;
        updateActiveItem();
    }

    function closeCommandPalette() {
        if (!overlay) return;
        overlay.style.display = 'none';
    }

    window.openShortcutsHelp = function() {
        if (helpOverlay) helpOverlay.style.display = 'flex';
    };

    window.closeShortcutsHelp = function() {
        if (helpOverlay) helpOverlay.style.display = 'none';
    };

    // Close on backdrop click
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) closeCommandPalette();
    });
    if (helpOverlay) {
        helpOverlay.addEventListener('click', (e) => {
            if (e.target === helpOverlay) window.closeShortcutsHelp();
        });
    }

    // Input filtering
    input.addEventListener('input', () => {
        filterItems(input.value.trim().toLowerCase());
        activeIndex = 0;
        updateActiveItem();
    });

    function filterItems(query) {
        const items = resultsContainer.querySelectorAll('.palette-item');
        items.forEach(item => {
            const title = (item.dataset.title || item.textContent).toLowerCase();
            if (query === '' || title.includes(query)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    function getVisibleItems() {
        return Array.from(resultsContainer.querySelectorAll('.palette-item')).filter(
            item => item.style.display !== 'none'
        );
    }

    function updateActiveItem() {
        const visible = getVisibleItems();
        visible.forEach((item, idx) => {
            if (idx === activeIndex) {
                item.classList.add('active');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('active');
            }
        });
    }

    // Arrow navigation inside input
    input.addEventListener('keydown', (e) => {
        const visible = getVisibleItems();
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (visible.length > 0) {
                activeIndex = (activeIndex + 1) % visible.length;
                updateActiveItem();
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (visible.length > 0) {
                activeIndex = (activeIndex - 1 + visible.length) % visible.length;
                updateActiveItem();
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (visible[activeIndex]) {
                visible[activeIndex].click();
            }
        } else if (e.key === 'Escape') {
            closeCommandPalette();
        }
    });

    // Global Key Listener
    document.addEventListener('keydown', (e) => {
        const isInput = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName) ||
                        document.activeElement.isContentEditable;

        // 1. Cmd+K / Ctrl+K (always intercept)
        if ((e.metaKey || e.ctrlKey) && (e.key === 'k' || e.key === 'K')) {
            e.preventDefault();
            toggleCommandPalette();
            return;
        }

        // 2. Escape to close palette or modal
        if (e.key === 'Escape') {
            if (overlay && overlay.style.display === 'flex') {
                closeCommandPalette();
                return;
            }
            if (helpOverlay && helpOverlay.style.display === 'flex') {
                window.closeShortcutsHelp();
                return;
            }
            const dupModal = document.getElementById('duplicateModalOverlay');
            if (dupModal && dupModal.style.display === 'flex') {
                dupModal.style.display = 'none';
                return;
            }
        }

        // Skip single-key shortcuts when typing in an input
        if (isInput) return;

        // 3. Single-Key Global Shortcuts
        const key = e.key.toLowerCase();
        if (key === 'c') {
            e.preventDefault();
            window.location.href = '/people/create';
        } else if (key === 'd') {
            e.preventDefault();
            window.location.href = '/opportunities/create';
        } else if (key === 't') {
            e.preventDefault();
            window.location.href = '/tasks/create';
        } else if (e.key === '?') {
            e.preventDefault();
            window.openShortcutsHelp();
        }
    });
}
