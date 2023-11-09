/**
 * Module: TYPO3/CMS/Mkcontentai/ContextMenu
 *
 * JavaScript to handle the click action of the "Hello World" context menu item
 * @exports TYPO3/CMS/Mkcontentai/ContextMenu
 */
define(function () {
    'use strict';

    /**
     * @exports TYPO3/CMS/Mkcontentai/ContextMenu
     */
    var ContextMenu = {};

    /**
     * @param {string} table
     * @param {int} uid of the page
     */
    ContextMenu.upscale = function (table, uid) {
        top.TYPO3.Backend.ContentContainer.setUrl($(this).attr('data-navigate-uri'));
    };
    /**
     * @param {string} table
     * @param {int} uid of the page
     */
    ContextMenu.extend = function (table, uid) {
        top.TYPO3.Backend.ContentContainer.setUrl($(this).attr('data-navigate-uri'));
    };


    return ContextMenu;
});
