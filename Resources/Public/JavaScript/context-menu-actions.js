/**
 * Module: @t3docs/mkcontentai/context-menu-actions
 */

class ContextMenuActions {

    upscale(table, uid, data) {
        top.TYPO3.Backend.ContentContainer.setUrl(data.navigateUri);
    };

    extend(table, uid, data) {
        top.TYPO3.Backend.ContentContainer.setUrl(data.navigateUri);
    };

    alt(table, uid, data) {
        top.TYPO3.Backend.ContentContainer.setUrl(data.navigateUri);
    };
}

export default new ContextMenuActions();
