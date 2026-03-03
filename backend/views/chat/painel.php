<?php

/** @var yii\web\View $this */
/** @var common\models\Atendente|null $atendente */
/** @var int $filaCount */

use yii\helpers\Url;

$this->title = 'Dashboard de Atendimento';
$this->params['breadcrumbs'][] = $this->title;

// URLs AJAX
$messagesUrl = Url::to(['/chat/messages']);
$sendMessageUrl = Url::to(['/chat/send-message']);
$sendMediaUrl = Url::to(['/chat/send-media']);
$editMessageUrl = Url::to(['/chat/edit-message']);
$deleteMessageUrl = Url::to(['/chat/delete-message']);
$forwardMessageUrl = Url::to(['/chat/forward-message']);
$reactMessageUrl = Url::to(['/chat/react-message']);
$updateContactNameUrl = Url::to(['/chat/update-contact-name']);
$filaJsonUrl = Url::to(['/conversa/fila-json']);
$pegarAjaxUrl = Url::to(['/conversa/pegar-ajax']);
$finalizarAjaxUrl = Url::to(['/conversa/finalizar-ajax']);
$devolverAjaxUrl = Url::to(['/conversa/devolver-ajax']);
$minhasConversasUrl = Url::to(['/conversa/minhas-conversas']);
$csrfToken = Yii::$app->request->csrfToken;
$filaCount = $filaCount ?? 0;
?>

<style>
/* ===== DASHBOARD LAYOUT ===== */
.dash-wrapper {
    height: calc(100vh - 120px);
    min-height: 500px;
    display: flex;
    flex-direction: column;
}

/* ===== TOPBAR ===== */
.dash-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 16px;
    background: #075e54;
    color: #fff;
    border-radius: 4px 4px 0 0;
    flex-shrink: 0;
}
.dash-topbar .dash-title {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
}
.dash-topbar .dash-actions .btn {
    font-size: 0.78rem;
    padding: 4px 12px;
    margin-left: 6px;
    border-radius: 4px;
}
.btn-fila {
    background: #ffc107 !important;
    color: #333 !important;
    border: none;
    font-weight: 600;
}
.btn-fila .badge {
    background: #e74c3c;
    color: #fff;
    font-size: 0.65rem;
    padding: 2px 5px;
    border-radius: 8px;
    margin-left: 4px;
}
.btn-fila.has-waiting {
    animation: pulse-fila 2s ease-in-out infinite;
}
@keyframes pulse-fila {
    0%, 100% { box-shadow: 0 0 0 0 rgba(255,193,7,0.5); }
    50% { box-shadow: 0 0 0 6px rgba(255,193,7,0); }
}

/* ===== GRID HORIZONTAL ===== */
.dash-grid {
    flex: 1;
    display: flex;
    gap: 6px;
    padding: 6px;
    background: #dfe6e9;
    overflow-x: auto;
    overflow-y: hidden;
    border-radius: 0 0 4px 4px;
    scroll-behavior: smooth;
}
.dash-grid::-webkit-scrollbar {
    height: 8px;
}
.dash-grid::-webkit-scrollbar-track {
    background: #dfe6e9;
    border-radius: 4px;
}
.dash-grid::-webkit-scrollbar-thumb {
    background: #b2bec3;
    border-radius: 4px;
}
.dash-grid::-webkit-scrollbar-thumb:hover {
    background: #636e72;
}

/* ===== SLOT ===== */
.slot {
    display: flex;
    flex-direction: column;
    background: #fff;
    border: 2px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    position: relative;
    transition: border-color 0.3s, box-shadow 0.3s;
    box-shadow: 0 1px 4px rgba(0,0,0,0.06);
    min-width: 360px;
    max-width: 420px;
    flex-shrink: 0;
    height: 100%;
}
.slot.slot-empty {
    background: #f5f6fa;
    display: flex;
    align-items: center;
    justify-content: center;
    border-style: dashed;
    border-color: #ccc;
}
.slot.slot-empty .slot-placeholder {
    text-align: center;
    color: #bbb;
}
.slot.slot-empty .slot-placeholder i {
    font-size: 1.8rem;
    margin-bottom: 6px;
    display: block;
    opacity: 0.5;
}
.slot.slot-empty .slot-placeholder span {
    font-size: 0.78rem;
}
.slot.slot-active {
    border-color: #b2dfdb;
    border-style: solid;
}
.slot.slot-flash {
    animation: flash-border 0.8s ease-in-out infinite;
    border-width: 3px;
}
@keyframes flash-border {
    0%, 100% { border-color: #e74c3c; box-shadow: 0 0 12px rgba(231,76,60,0.6), inset 0 0 4px rgba(231,76,60,0.15); }
    50% { border-color: #ff0000; box-shadow: 0 0 22px rgba(255,0,0,0.7), inset 0 0 6px rgba(255,0,0,0.2); }
}

/* ===== SLOT HEADER (estilo card WhatsApp) ===== */
.slot-header {
    display: flex;
    align-items: center;
    padding: 8px 10px;
    background: #fff;
    border-bottom: 1px solid #e8e8e8;
    flex-shrink: 0;
    min-height: 48px;
    gap: 8px;
}
.slot-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
    text-transform: uppercase;
}
.slot-header-info {
    flex: 1;
    min-width: 0;
}
.slot-header-info .slot-client-name {
    font-weight: 600;
    font-size: 0.85rem;
    color: #222;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}
.slot-client-name .btn-edit-name { opacity: 0; transition: opacity 0.2s; }
.slot-client-name:hover .btn-edit-name { opacity: 1; }
.slot-header-info .slot-client-number {
    font-size: 0.7rem;
    color: #999;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}
.slot-header-actions {
    display: flex;
    gap: 4px;
    flex-shrink: 0;
    align-items: center;
}
.slot-header-actions .btn-slot-action {
    background: none;
    border: none;
    color: #888;
    font-size: 0.85rem;
    cursor: pointer;
    padding: 4px 6px;
    border-radius: 50%;
    transition: background 0.2s, color 0.2s;
    line-height: 1;
}
.slot-header-actions .btn-slot-action:hover {
    background: #f0f0f0;
    color: #333;
}

/* Dropdown menu no slot */
.slot-dropdown {
    position: absolute;
    top: 46px;
    right: 6px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 3px 14px rgba(0,0,0,0.18);
    padding: 4px 0;
    z-index: 50;
    min-width: 140px;
    display: none;
}
.slot-dropdown.open { display: block; }
.slot-dropdown .drop-item {
    padding: 7px 14px;
    font-size: 0.8rem;
    color: #333;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}
.slot-dropdown .drop-item:hover { background: #f5f5f5; }
.slot-dropdown .drop-item i { width: 14px; text-align: center; color: #888; }
.slot-dropdown .drop-item.text-danger { color: #e74c3c; }
.slot-dropdown .drop-item.text-danger i { color: #e74c3c; }
.slot-dropdown .drop-item.text-warning { color: #e67e22; }
.slot-dropdown .drop-item.text-warning i { color: #e67e22; }

/* ===== SLOT MESSAGES ===== */
.slot-messages {
    flex: 1;
    overflow-y: auto;
    padding: 8px 8px;
    background-color: #efeae2;
    background-image: url("data:image/svg+xml,%3Csvg width='200' height='200' xmlns='http://www.w3.org/2000/svg'%3E%3Cdefs%3E%3Cpattern id='p' width='40' height='40' patternUnits='userSpaceOnUse' patternTransform='rotate(45)'%3E%3Ccircle cx='4' cy='4' r='1' fill='%23d6d0c5' opacity='0.5'/%3E%3Ccircle cx='24' cy='14' r='0.8' fill='%23d6d0c5' opacity='0.4'/%3E%3Ccircle cx='14' cy='28' r='1.2' fill='%23d6d0c5' opacity='0.35'/%3E%3Ccircle cx='34' cy='34' r='0.6' fill='%23d6d0c5' opacity='0.45'/%3E%3Cpath d='M8 20 Q10 18 12 20 Q10 22 8 20Z' fill='none' stroke='%23d6d0c5' stroke-width='0.5' opacity='0.3'/%3E%3Cpath d='M28 6 L30 4 L32 6 L30 8Z' fill='none' stroke='%23d6d0c5' stroke-width='0.5' opacity='0.25'/%3E%3Cpath d='M18 8 Q20 6 22 8' fill='none' stroke='%23d6d0c5' stroke-width='0.5' opacity='0.3'/%3E%3Cpath d='M2 34 L4 32 L6 34' fill='none' stroke='%23d6d0c5' stroke-width='0.5' opacity='0.3'/%3E%3C/pattern%3E%3C/defs%3E%3Crect width='200' height='200' fill='url(%23p)'/%3E%3C/svg%3E");
    font-size: 0.8rem;
}
.slot-messages .slot-loading {
    text-align: center;
    padding: 30px;
    color: #999;
    font-size: 0.78rem;
}
.slot-messages .slot-loading i { animation: spin 1s linear infinite; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

/* Message row: avatar badge + bubble */
.slot-messages .msg-row {
    display: flex;
    align-items: flex-start;
    margin-bottom: 4px;
    clear: both;
}
.slot-messages .msg-row.sent {
    flex-direction: row-reverse;
}
.slot-messages .msg-badge {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.5rem;
    font-weight: 700;
    color: #fff;
    flex-shrink: 0;
    margin-top: 2px;
}
.slot-messages .msg-row.received .msg-badge { margin-right: 5px; }
.slot-messages .msg-row.sent .msg-badge { margin-left: 5px; }

/* Bubbles */
.slot-messages .msg-bubble {
    max-width: 80%;
    padding: 5px 8px;
    border-radius: 8px;
    position: relative;
    word-wrap: break-word;
    box-shadow: 0 1px 2px rgba(0,0,0,0.08);
    font-size: 0.8rem;
}
.slot-messages .msg-bubble.received {
    background: #fff;
    border-top-left-radius: 0;
}
.slot-messages .msg-bubble.sent {
    background: #d9fdd3;
    border-top-right-radius: 0;
}
.slot-messages .msg-sender {
    font-size: 0.68rem;
    font-weight: 600;
    color: #06cf9c;
    margin-bottom: 1px;
}
.slot-messages .msg-text {
    font-size: 0.8rem;
    color: #111b21;
    line-height: 1.35;
    margin-bottom: 1px;
}
.slot-messages .msg-media .media-image {
    max-width: 140px;
    max-height: 100px;
    border-radius: 6px;
    cursor: pointer;
    display: block;
}
.slot-messages .msg-media .media-sticker {
    max-width: 70px;
    max-height: 70px;
    display: block;
}
.slot-messages .msg-media .media-audio {
    max-width: 180px;
    height: 30px;
    display: block;
}
.slot-messages .msg-media .media-video {
    max-width: 160px;
    max-height: 110px;
    border-radius: 6px;
    display: block;
}
.slot-messages .msg-media .media-doc {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    background: rgba(0,0,0,0.05);
    border-radius: 6px;
    color: #027eb5;
    text-decoration: none;
    font-size: 0.75rem;
}
.slot-messages .msg-media .media-icon {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8px;
    background: rgba(0,0,0,0.05);
    border-radius: 6px;
    font-size: 0.75rem;
    color: #667781;
}
.slot-messages .msg-meta {
    text-align: right;
    font-size: 0.62rem;
    color: #667781;
    margin-top: 0;
}
.slot-messages .msg-meta .msg-status { margin-left: 2px; }
.slot-messages .msg-meta .msg-status.read { color: #53bdeb; }
.slot-messages .msg-meta .msg-sender-name {
    font-size: 0.6rem;
    color: #075e54;
    font-weight: 600;
    margin-right: 3px;
}
.slot-messages .msg-meta .msg-edited-label {
    font-size: 0.6rem;
    color: #8696a0;
    font-style: italic;
    margin-right: 3px;
}
.slot-messages .msg-date-separator {
    text-align: center;
    margin: 6px 0;
    clear: both;
}
.slot-messages .msg-date-separator span {
    background: #e1f3fb;
    padding: 2px 10px;
    border-radius: 8px;
    font-size: 0.68rem;
    color: #54656f;
}
.slot-messages .msg-clearfix { clear: both; }
.slot-messages .msg-quoted {
    background: rgba(0,0,0,0.06);
    border-left: 2px solid #06cf9c;
    border-radius: 4px;
    padding: 2px 6px;
    margin-bottom: 2px;
    font-size: 0.72rem;
    color: #667781;
    max-height: 34px;
    overflow: hidden;
    cursor: pointer;
}
.slot-messages .msg-bubble.sent .msg-quoted { border-left-color: #075e54; }
.slot-messages .msg-reactions {
    display: flex;
    flex-wrap: wrap;
    gap: 2px;
    margin-top: 1px;
}
.slot-messages .msg-reaction-chip {
    display: inline-flex;
    align-items: center;
    gap: 1px;
    padding: 0 4px;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    font-size: 0.7rem;
    cursor: pointer;
}

/* Hover actions (reply, react, edit, delete) */
.slot-messages .msg-hover-actions {
    position: absolute;
    top: 2px;
    right: 2px;
    display: none;
    background: rgba(255,255,255,0.95);
    border-radius: 12px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.15);
    padding: 1px 3px;
    gap: 1px;
    z-index: 10;
    align-items: center;
}
.slot-messages .msg-bubble.sent .msg-hover-actions { background: rgba(217,253,211,0.95); }
.slot-messages .msg-bubble:hover .msg-hover-actions,
.slot-messages .msg-hover-actions:hover { display: flex !important; }
.slot-messages .msg-hover-actions .hover-btn {
    font-size: 0.72rem;
    padding: 3px 5px;
    cursor: pointer;
    color: #667781;
    border-radius: 50%;
    line-height: 1;
}
.slot-messages .msg-hover-actions .hover-btn:hover { background: rgba(0,0,0,0.06); color: #075e54; }
.slot-messages .msg-hover-actions .hover-btn.hover-delete:hover { color: #e74c3c; }

/* Emoji picker popup */
.emoji-picker-popup {
    position: fixed;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.22);
    padding: 0;
    z-index: 9998;
    display: none;
    width: 280px;
    max-height: 320px;
    overflow: hidden;
}
.emoji-picker-popup .emoji-tabs {
    display: flex;
    border-bottom: 1px solid #e8e8e8;
    padding: 0 4px;
    background: #f8f9fa;
    border-radius: 12px 12px 0 0;
}
.emoji-picker-popup .emoji-tab {
    flex: 1;
    text-align: center;
    padding: 6px 2px;
    cursor: pointer;
    font-size: 1rem;
    opacity: 0.5;
    border-bottom: 2px solid transparent;
    transition: opacity 0.2s;
}
.emoji-picker-popup .emoji-tab:hover { opacity: 0.8; }
.emoji-picker-popup .emoji-tab.active { opacity: 1; border-bottom-color: #128c7e; }
.emoji-picker-popup .emoji-panel {
    display: none;
    padding: 6px;
    overflow-y: auto;
    max-height: 240px;
}
.emoji-picker-popup .emoji-panel.active { display: block; }
.emoji-picker-popup .emoji-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 1px;
}
.emoji-picker-popup .emoji-option {
    font-size: 1.2rem;
    text-align: center;
    padding: 4px 2px;
    cursor: pointer;
    border-radius: 6px;
    line-height: 1.2;
    transition: background 0.15s, transform 0.1s;
}
.emoji-picker-popup .emoji-option:hover { background: #f0f2f5; transform: scale(1.2); }

/* Context menu danger item */
.msg-context-menu .ctx-item.ctx-danger { color: #e74c3c; }
.msg-context-menu .ctx-item.ctx-danger i { color: #e74c3c; }

/* Deleted message */
.slot-messages .msg-deleted-text {
    font-style: italic;
    color: #8696a0;
    font-size: 0.78rem;
}

/* Forward modal */
.forward-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.45);
    z-index: 9992;
    align-items: center;
    justify-content: center;
}
.forward-overlay.open { display: flex; }
.forward-box {
    background: #fff;
    border-radius: 12px;
    width: 320px;
    max-height: 400px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.25);
    animation: modalPop 0.2s ease-out;
    overflow: hidden;
}
.forward-box .forward-header {
    padding: 14px 16px;
    background: #075e54;
    color: #fff;
    font-weight: 600;
    font-size: 0.9rem;
}
.forward-box .forward-list {
    max-height: 300px;
    overflow-y: auto;
    padding: 6px 0;
}
.forward-box .forward-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 16px;
    cursor: pointer;
    transition: background 0.15s;
    font-size: 0.85rem;
}
.forward-box .forward-item:hover { background: #f0f2f5; }
.forward-box .forward-item .fw-avatar {
    width: 34px; height: 34px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.7rem; font-weight: 700; color: #fff; flex-shrink: 0;
}
.forward-box .forward-item .fw-name { font-weight: 500; }
.forward-box .forward-empty {
    text-align: center; padding: 30px; color: #999; font-size: 0.85rem;
}
.forward-box .forward-cancel {
    display: block; width: 100%; padding: 10px;
    border: none; border-top: 1px solid #e8e8e8;
    background: #f8f9fa; color: #666; cursor: pointer;
    font-size: 0.85rem; text-align: center;
}
.forward-box .forward-cancel:hover { background: #eee; }

/* ===== SLOT REPLY BAR ===== */
.slot-reply-bar {
    display: none;
    padding: 3px 8px;
    background: #f0f2f5;
    border-top: 1px solid #e0e0e0;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}
.slot-reply-bar .reply-content {
    flex: 1;
    background: #fff;
    border-left: 3px solid #06cf9c;
    border-radius: 4px;
    padding: 3px 8px;
    font-size: 0.72rem;
    min-width: 0;
}
.slot-reply-bar .reply-sender { font-weight: 600; color: #06cf9c; font-size: 0.68rem; }
.slot-reply-bar .reply-text { color: #667781; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.slot-reply-bar .btn-close-reply {
    background: none; border: none; color: #667781; font-size: 0.85rem; cursor: pointer; padding: 2px;
}

/* ===== SLOT INPUT (estilo imagem 2) ===== */
.slot-input-area {
    padding: 6px 8px;
    background: #f0f2f5;
    border-top: 1px solid #e0e0e0;
    flex-shrink: 0;
}
.slot-input-row {
    display: flex;
    align-items: center;
    gap: 6px;
}
.slot-input-row .slot-text {
    flex: 1;
    border: 1px solid #e0e0e0;
    border-radius: 20px;
    padding: 7px 14px;
    font-size: 0.82rem;
    background: #fff;
    outline: none;
    resize: none;
    max-height: 70px;
    min-height: 32px;
    line-height: 1.35;
    transition: border-color 0.2s;
}
.slot-input-row .slot-text:focus { border-color: #128c7e; }
.slot-input-row .btn-slot-send {
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: #0088cc;
    color: #fff;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    cursor: pointer;
    flex-shrink: 0;
    transition: background 0.2s;
}
.slot-input-row .btn-slot-send:hover { background: #006699; }
.slot-actions-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 4px;
    padding: 0 2px;
}
.slot-actions-row .btn-slot-tool {
    background: none;
    border: none;
    color: #888;
    font-size: 0.9rem;
    cursor: pointer;
    padding: 2px 4px;
    border-radius: 4px;
    transition: color 0.2s;
}
.slot-actions-row .btn-slot-tool:hover { color: #075e54; }

/* ===== FILA DRAWER ===== */
.fila-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.35);
    z-index: 1050;
}
.fila-drawer {
    display: none;
    position: fixed;
    top: 0; right: 0;
    width: 400px;
    height: 100%;
    background: #fff;
    box-shadow: -4px 0 20px rgba(0,0,0,0.18);
    z-index: 1051;
    flex-direction: column;
}
.fila-drawer.open { display: flex; }
.fila-overlay.open { display: block; }
.fila-drawer-header {
    padding: 14px 18px;
    background: #075e54;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.fila-drawer-header h5 {
    margin: 0;
    font-size: 1.05rem;
    font-weight: 600;
}
.fila-drawer-header .btn-close-fila {
    background: none;
    border: none;
    color: rgba(255,255,255,0.8);
    font-size: 1.3rem;
    cursor: pointer;
}
.fila-drawer-header .btn-close-fila:hover { color: #fff; }
.fila-cards {
    flex: 1;
    overflow-y: auto;
    padding: 14px;
}
.fila-card {
    border-left: 4px solid #ffc107;
    background: #fff;
    border-radius: 6px;
    padding: 12px 14px;
    margin-bottom: 12px;
    box-shadow: 0 1px 6px rgba(0,0,0,0.08);
    transition: box-shadow 0.2s, transform 0.15s;
}
.fila-card:hover {
    box-shadow: 0 3px 12px rgba(0,0,0,0.14);
    transform: translateY(-1px);
}
.fila-card-nome {
    font-weight: 600;
    font-size: 0.92rem;
    color: #333;
}
.fila-card-nome i { color: #888; margin-right: 4px; }
.fila-card-numero {
    font-size: 0.78rem;
    color: #888;
    margin-top: 1px;
}
.fila-card-preview {
    font-size: 0.82rem;
    color: #555;
    margin-top: 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    background: #f8f9fa;
    padding: 4px 8px;
    border-radius: 4px;
    border-left: 3px solid #e0e0e0;
}
.fila-card-preview i { color: #999; margin-right: 4px; }
.fila-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 10px;
}
.fila-card-tempo {
    font-size: 0.75rem;
    color: #999;
}
.fila-card-tempo i { margin-right: 3px; }
.fila-card .btn-aceitar {
    background: #25d366;
    color: #fff;
    border: none;
    padding: 5px 16px;
    border-radius: 6px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.fila-card .btn-aceitar:hover { background: #128c7e; }
.fila-card .btn-aceitar:disabled { background: #ccc; cursor: not-allowed; }
.fila-empty {
    text-align: center;
    padding: 50px 20px;
    color: #bbb;
}
.fila-empty i { font-size: 2.5rem; margin-bottom: 10px; display: block; }

/* ===== CONTEXT MENU ===== */
.msg-context-menu {
    position: fixed;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 3px 14px rgba(0,0,0,0.18);
    padding: 4px 0;
    z-index: 9999;
    min-width: 150px;
    display: none;
}
.msg-context-menu .ctx-item {
    padding: 7px 14px;
    font-size: 0.82rem;
    color: #111b21;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}
.msg-context-menu .ctx-item:hover { background: #f5f6f6; }
.msg-context-menu .ctx-item i { width: 14px; text-align: center; color: #667781; }

/* ===== MODAL CONFIRM ===== */
.modal-confirm-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.45);
    z-index: 9990;
    align-items: center;
    justify-content: center;
}
.modal-confirm-overlay.open { display: flex; }
.modal-confirm-box {
    background: #fff;
    border-radius: 14px;
    padding: 28px 32px 20px;
    min-width: 320px;
    max-width: 400px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.25);
    text-align: center;
    animation: modalPop 0.2s ease-out;
}
@keyframes modalPop {
    0% { transform: scale(0.85); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
.modal-confirm-icon {
    font-size: 2.5rem;
    margin-bottom: 12px;
}
.modal-confirm-icon.warn { color: #f39c12; }
.modal-confirm-icon.danger { color: #e74c3c; }
.modal-confirm-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #222;
    margin-bottom: 6px;
}
.modal-confirm-text {
    font-size: 0.88rem;
    color: #666;
    margin-bottom: 22px;
    line-height: 1.4;
}
.modal-confirm-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
}
.modal-confirm-actions .btn-modal {
    padding: 9px 28px;
    border-radius: 8px;
    font-size: 0.88rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: background 0.2s, transform 0.1s;
}
.modal-confirm-actions .btn-modal:active { transform: scale(0.96); }
.modal-confirm-actions .btn-modal-cancel {
    background: #f0f2f5;
    color: #555;
}
.modal-confirm-actions .btn-modal-cancel:hover { background: #e0e3e7; }
.modal-confirm-actions .btn-modal-ok {
    color: #fff;
}
.modal-confirm-actions .btn-modal-ok.warn { background: #f39c12; }
.modal-confirm-actions .btn-modal-ok.warn:hover { background: #e67e22; }
.modal-confirm-actions .btn-modal-ok.danger { background: #e74c3c; }
.modal-confirm-actions .btn-modal-ok:hover { filter: brightness(0.9); }

/* ===== RESPONSIVE ===== */
@media (max-width: 600px) {
    .slot { min-width: 300px; max-width: 340px; }
    .fila-drawer { width: 100%; }
}
</style>

<div class="dash-wrapper">
    <!-- TOPBAR -->
    <div class="dash-topbar">
        <div class="d-flex align-items-center">
            <h5 class="dash-title"><i class="fas fa-headset"></i> Dashboard de Atendimento</h5>
            <?php if ($atendente): ?>
            <span style="margin-left:12px; font-size:0.78rem; color:rgba(255,255,255,0.7);">
                <?= \yii\helpers\Html::encode($atendente->nome) ?> &mdash;
                <span id="contadorConversas"><?= (int)$atendente->conversas_ativas ?></span>/<?= (int)$atendente->max_conversas ?>
            </span>
            <?php endif; ?>
        </div>
        <div class="dash-actions">
            <button class="btn btn-sm btn-danger" id="btnFinalizarTodas" title="Finalizar todas as conversas ativas" style="font-weight:600;">
                <i class="fas fa-check-double"></i> Finalizar Todas
            </button>
            <button class="btn btn-sm btn-fila <?= $filaCount > 0 ? 'has-waiting' : '' ?>" id="btnAbrirFila" title="Abrir fila de espera">
                <i class="fas fa-inbox"></i> Fila <span class="badge" id="filaBadge"><?= $filaCount ?></span>
            </button>
            <button class="btn btn-sm btn-light" id="btnRefresh" title="Atualizar">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
    </div>

    <!-- GRID 4x2 -->
    <div class="dash-grid" id="dashGrid">
        <?php for ($i = 0; $i < 8; $i++): ?>
        <div class="slot slot-empty" id="slot<?= $i ?>" data-slot="<?= $i ?>">
            <div class="slot-placeholder">
                <i class="fas fa-comment-slash"></i>
                <span>Slot disponivel</span>
            </div>
        </div>
        <?php endfor; ?>
    </div>
</div>

<!-- FILA DRAWER -->
<div class="fila-overlay" id="filaOverlay"></div>
<div class="fila-drawer" id="filaDrawer">
    <div class="fila-drawer-header">
        <h5><i class="fas fa-inbox"></i> Fila de Espera (<span id="filaDrawerCount">0</span>)</h5>
        <button class="btn-close-fila" id="btnFecharFila"><i class="fas fa-times"></i></button>
    </div>
    <div class="fila-cards" id="filaCards">
        <div class="fila-empty"><i class="fas fa-check-circle"></i><span>Nenhuma conversa na fila.</span></div>
    </div>
</div>

<!-- CONTEXT MENU -->
<div class="msg-context-menu" id="msgContextMenu">
    <div class="ctx-item" data-action="reply"><i class="fas fa-reply"></i> Responder</div>
    <div class="ctx-item" data-action="react"><i class="fas fa-smile"></i> Reagir</div>
    <div class="ctx-item ctx-edit-only" data-action="edit" style="display:none;"><i class="fas fa-pen"></i> Editar</div>
    <div class="ctx-item" data-action="forward"><i class="fas fa-share"></i> Encaminhar</div>
    <div class="ctx-item" data-action="copy"><i class="fas fa-copy"></i> Copiar</div>
    <div class="ctx-item ctx-delete-only ctx-danger" data-action="delete" style="display:none;"><i class="fas fa-trash-alt"></i> Excluir</div>
</div>

<!-- EMOJI PICKER -->
<div class="emoji-picker-popup" id="emojiPicker">
    <div class="emoji-tabs">
        <span class="emoji-tab active" data-panel="faces">😀</span>
        <span class="emoji-tab" data-panel="gestos">👋</span>
        <span class="emoji-tab" data-panel="coracoes">❤️</span>
        <span class="emoji-tab" data-panel="objetos">🎉</span>
        <span class="emoji-tab" data-panel="animais">🐶</span>
    </div>
    <div class="emoji-panel active" data-panel="faces">
        <div class="emoji-grid">
            <span class="emoji-option" data-emoji="😀">😀</span>
            <span class="emoji-option" data-emoji="😃">😃</span>
            <span class="emoji-option" data-emoji="😄">😄</span>
            <span class="emoji-option" data-emoji="😁">😁</span>
            <span class="emoji-option" data-emoji="😂">😂</span>
            <span class="emoji-option" data-emoji="🤣">🤣</span>
            <span class="emoji-option" data-emoji="😅">😅</span>
            <span class="emoji-option" data-emoji="😊">😊</span>
            <span class="emoji-option" data-emoji="😇">😇</span>
            <span class="emoji-option" data-emoji="🙂">🙂</span>
            <span class="emoji-option" data-emoji="😉">😉</span>
            <span class="emoji-option" data-emoji="😍">😍</span>
            <span class="emoji-option" data-emoji="🥰">🥰</span>
            <span class="emoji-option" data-emoji="😘">😘</span>
            <span class="emoji-option" data-emoji="😜">😜</span>
            <span class="emoji-option" data-emoji="🤪">🤪</span>
            <span class="emoji-option" data-emoji="😎">😎</span>
            <span class="emoji-option" data-emoji="🤩">🤩</span>
            <span class="emoji-option" data-emoji="😏">😏</span>
            <span class="emoji-option" data-emoji="😒">😒</span>
            <span class="emoji-option" data-emoji="🙄">🙄</span>
            <span class="emoji-option" data-emoji="😮">😮</span>
            <span class="emoji-option" data-emoji="😲">😲</span>
            <span class="emoji-option" data-emoji="😳">😳</span>
            <span class="emoji-option" data-emoji="🥺">🥺</span>
            <span class="emoji-option" data-emoji="😢">😢</span>
            <span class="emoji-option" data-emoji="😭">😭</span>
            <span class="emoji-option" data-emoji="😤">😤</span>
            <span class="emoji-option" data-emoji="😡">😡</span>
            <span class="emoji-option" data-emoji="🤬">🤬</span>
            <span class="emoji-option" data-emoji="😱">😱</span>
            <span class="emoji-option" data-emoji="😰">😰</span>
            <span class="emoji-option" data-emoji="🤔">🤔</span>
            <span class="emoji-option" data-emoji="🤫">🤫</span>
            <span class="emoji-option" data-emoji="🤭">🤭</span>
            <span class="emoji-option" data-emoji="🤗">🤗</span>
            <span class="emoji-option" data-emoji="😴">😴</span>
            <span class="emoji-option" data-emoji="🤮">🤮</span>
            <span class="emoji-option" data-emoji="🤧">🤧</span>
            <span class="emoji-option" data-emoji="😷">😷</span>
            <span class="emoji-option" data-emoji="🤡">🤡</span>
            <span class="emoji-option" data-emoji="💀">💀</span>
        </div>
    </div>
    <div class="emoji-panel" data-panel="gestos">
        <div class="emoji-grid">
            <span class="emoji-option" data-emoji="👍">👍</span>
            <span class="emoji-option" data-emoji="👎">👎</span>
            <span class="emoji-option" data-emoji="👏">👏</span>
            <span class="emoji-option" data-emoji="🙌">🙌</span>
            <span class="emoji-option" data-emoji="🤝">🤝</span>
            <span class="emoji-option" data-emoji="🙏">🙏</span>
            <span class="emoji-option" data-emoji="✌️">✌️</span>
            <span class="emoji-option" data-emoji="🤞">🤞</span>
            <span class="emoji-option" data-emoji="🤙">🤙</span>
            <span class="emoji-option" data-emoji="👋">👋</span>
            <span class="emoji-option" data-emoji="💪">💪</span>
            <span class="emoji-option" data-emoji="☝️">☝️</span>
            <span class="emoji-option" data-emoji="👆">👆</span>
            <span class="emoji-option" data-emoji="👇">👇</span>
            <span class="emoji-option" data-emoji="👈">👈</span>
            <span class="emoji-option" data-emoji="👉">👉</span>
            <span class="emoji-option" data-emoji="🤦">🤦</span>
            <span class="emoji-option" data-emoji="🤷">🤷</span>
            <span class="emoji-option" data-emoji="💃">💃</span>
            <span class="emoji-option" data-emoji="🕺">🕺</span>
            <span class="emoji-option" data-emoji="🤳">🤳</span>
        </div>
    </div>
    <div class="emoji-panel" data-panel="coracoes">
        <div class="emoji-grid">
            <span class="emoji-option" data-emoji="❤️">❤️</span>
            <span class="emoji-option" data-emoji="🧡">🧡</span>
            <span class="emoji-option" data-emoji="💛">💛</span>
            <span class="emoji-option" data-emoji="💚">💚</span>
            <span class="emoji-option" data-emoji="💙">💙</span>
            <span class="emoji-option" data-emoji="💜">💜</span>
            <span class="emoji-option" data-emoji="🖤">🖤</span>
            <span class="emoji-option" data-emoji="🤍">🤍</span>
            <span class="emoji-option" data-emoji="💔">💔</span>
            <span class="emoji-option" data-emoji="❣️">❣️</span>
            <span class="emoji-option" data-emoji="💕">💕</span>
            <span class="emoji-option" data-emoji="💞">💞</span>
            <span class="emoji-option" data-emoji="💓">💓</span>
            <span class="emoji-option" data-emoji="💗">💗</span>
            <span class="emoji-option" data-emoji="💖">💖</span>
            <span class="emoji-option" data-emoji="💘">💘</span>
            <span class="emoji-option" data-emoji="💝">💝</span>
            <span class="emoji-option" data-emoji="💟">💟</span>
            <span class="emoji-option" data-emoji="💋">💋</span>
            <span class="emoji-option" data-emoji="💌">💌</span>
            <span class="emoji-option" data-emoji="💐">💐</span>
        </div>
    </div>
    <div class="emoji-panel" data-panel="objetos">
        <div class="emoji-grid">
            <span class="emoji-option" data-emoji="🎉">🎉</span>
            <span class="emoji-option" data-emoji="🎊">🎊</span>
            <span class="emoji-option" data-emoji="🎁">🎁</span>
            <span class="emoji-option" data-emoji="🎂">🎂</span>
            <span class="emoji-option" data-emoji="🔥">🔥</span>
            <span class="emoji-option" data-emoji="⭐">⭐</span>
            <span class="emoji-option" data-emoji="🌟">🌟</span>
            <span class="emoji-option" data-emoji="✨">✨</span>
            <span class="emoji-option" data-emoji="💯">💯</span>
            <span class="emoji-option" data-emoji="💥">💥</span>
            <span class="emoji-option" data-emoji="🏆">🏆</span>
            <span class="emoji-option" data-emoji="🎵">🎵</span>
            <span class="emoji-option" data-emoji="🎶">🎶</span>
            <span class="emoji-option" data-emoji="☀️">☀️</span>
            <span class="emoji-option" data-emoji="🌈">🌈</span>
            <span class="emoji-option" data-emoji="⚡">⚡</span>
            <span class="emoji-option" data-emoji="🍕">🍕</span>
            <span class="emoji-option" data-emoji="🍺">🍺</span>
            <span class="emoji-option" data-emoji="☕">☕</span>
            <span class="emoji-option" data-emoji="🚗">🚗</span>
            <span class="emoji-option" data-emoji="✅">✅</span>
            <span class="emoji-option" data-emoji="❌">❌</span>
            <span class="emoji-option" data-emoji="⚠️">⚠️</span>
            <span class="emoji-option" data-emoji="📌">📌</span>
            <span class="emoji-option" data-emoji="📎">📎</span>
            <span class="emoji-option" data-emoji="📞">📞</span>
            <span class="emoji-option" data-emoji="💰">💰</span>
            <span class="emoji-option" data-emoji="🔑">🔑</span>
        </div>
    </div>
    <div class="emoji-panel" data-panel="animais">
        <div class="emoji-grid">
            <span class="emoji-option" data-emoji="🐶">🐶</span>
            <span class="emoji-option" data-emoji="🐱">🐱</span>
            <span class="emoji-option" data-emoji="🐭">🐭</span>
            <span class="emoji-option" data-emoji="🐰">🐰</span>
            <span class="emoji-option" data-emoji="🦊">🦊</span>
            <span class="emoji-option" data-emoji="🐻">🐻</span>
            <span class="emoji-option" data-emoji="🐼">🐼</span>
            <span class="emoji-option" data-emoji="🐸">🐸</span>
            <span class="emoji-option" data-emoji="🐵">🐵</span>
            <span class="emoji-option" data-emoji="🦁">🦁</span>
            <span class="emoji-option" data-emoji="🐔">🐔</span>
            <span class="emoji-option" data-emoji="🐧">🐧</span>
            <span class="emoji-option" data-emoji="🦋">🦋</span>
            <span class="emoji-option" data-emoji="🐢">🐢</span>
            <span class="emoji-option" data-emoji="🐍">🐍</span>
            <span class="emoji-option" data-emoji="🐳">🐳</span>
            <span class="emoji-option" data-emoji="🐠">🐠</span>
            <span class="emoji-option" data-emoji="🌸">🌸</span>
            <span class="emoji-option" data-emoji="🌺">🌺</span>
            <span class="emoji-option" data-emoji="🌻">🌻</span>
            <span class="emoji-option" data-emoji="🌹">🌹</span>
        </div>
    </div>
</div>

<!-- FORWARD MODAL -->
<div class="forward-overlay" id="forwardOverlay">
    <div class="forward-box">
        <div class="forward-header"><i class="fas fa-share"></i> Encaminhar para...</div>
        <div class="forward-list" id="forwardList"></div>
        <button class="forward-cancel" id="forwardCancel">Cancelar</button>
    </div>
</div>

<!-- MODAL CONFIRM -->
<div class="modal-confirm-overlay" id="modalConfirm">
    <div class="modal-confirm-box">
        <div class="modal-confirm-icon" id="modalIcon"><i class="fas fa-question-circle"></i></div>
        <div class="modal-confirm-title" id="modalTitle">Confirmar</div>
        <div class="modal-confirm-text" id="modalText">Tem certeza?</div>
        <div class="modal-confirm-actions">
            <button class="btn-modal btn-modal-cancel" id="modalCancel">Cancelar</button>
            <button class="btn-modal btn-modal-ok" id="modalOk">Confirmar</button>
        </div>
    </div>
</div>

<!-- FILE INPUT GLOBAL -->
<input type="file" id="globalFileInput" style="display:none;" accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx,.zip,.rar,.txt">

<script>
document.addEventListener('DOMContentLoaded', function() {
    // ==================== URLs ====================
    var messagesUrl = '<?= $messagesUrl ?>';
    var sendMessageUrl = '<?= $sendMessageUrl ?>';
    var sendMediaUrl = '<?= $sendMediaUrl ?>';
    var editMessageUrl = '<?= $editMessageUrl ?>';
    var deleteMessageUrl = '<?= $deleteMessageUrl ?>';
    var forwardMessageUrl = '<?= $forwardMessageUrl ?>';
    var reactMessageUrl = '<?= $reactMessageUrl ?>';
    var updateContactNameUrl = '<?= $updateContactNameUrl ?>';
    var filaJsonUrl = '<?= $filaJsonUrl ?>';
    var pegarAjaxUrl = '<?= $pegarAjaxUrl ?>';
    var finalizarAjaxUrl = '<?= $finalizarAjaxUrl ?>';
    var devolverAjaxUrl = '<?= $devolverAjaxUrl ?>';
    var minhasConversasUrl = '<?= $minhasConversasUrl ?>';
    var csrfToken = '<?= $csrfToken ?>';

    // ==================== AVATAR COLORS ====================
    var AVATAR_COLORS = ['#e17055','#00b894','#0984e3','#6c5ce7','#fdcb6e','#e84393','#00cec9','#d63031','#a29bfe','#55efc4','#fab1a0','#74b9ff'];
    function getAvatarColor(name) {
        var sum = 0;
        for (var i = 0; i < (name||'').length; i++) sum += name.charCodeAt(i);
        return AVATAR_COLORS[sum % AVATAR_COLORS.length];
    }
    function getInitials(name) {
        if (!name) return '?';
        var parts = name.trim().split(/\s+/);
        if (parts.length >= 2) return (parts[0][0] + parts[parts.length-1][0]).toUpperCase();
        return name.substring(0, 2).toUpperCase();
    }

    // ==================== STATE ====================
    var MAX_SLOTS = 8;
    var slots = [];
    for (var i = 0; i < MAX_SLOTS; i++) {
        slots.push({
            conversaId: null, chatId: null, chatJid: null,
            clienteNome: null, clienteNumero: null,
            lastMsgId: 0, lastTimestamp: 0, hasNewMsg: false, isSending: false,
            replyingToKey: null, replyingToText: null, replyingToSender: null,
            editingKey: null, editingText: null,
            avatarColor: '#999'
        });
    }
    var contextTarget = null;
    var contextSlotIdx = null;
    var activeFileSlot = null;
    var msgPollTimer = null;
    var filaPollTimer = null;

    // ==================== SLOT MANAGEMENT ====================
    function findEmptySlot() {
        for (var i = 0; i < MAX_SLOTS; i++) { if (!slots[i].conversaId) return i; }
        return -1;
    }
    function findSlotByConversaId(cid) {
        for (var i = 0; i < MAX_SLOTS; i++) { if (slots[i].conversaId == cid) return i; }
        return -1;
    }
    function findSlotByChatId(cid) {
        for (var i = 0; i < MAX_SLOTS; i++) { if (slots[i].chatId == cid) return i; }
        return -1;
    }

    function activateSlot(idx, data) {
        var s = slots[idx];
        s.conversaId = data.conversa_id || data.conversaId;
        s.chatId = data.chat_id || data.chatId;
        s.chatJid = data.chat_jid || data.chatJid;
        s.clienteNome = data.cliente_nome || data.clienteNome || 'Cliente';
        s.clienteNumero = data.cliente_numero || data.clienteNumero || '';
        s.lastMsgId = 0;
        s.hasNewMsg = false;
        s.avatarColor = getAvatarColor(s.clienteNome);

        var el = document.getElementById('slot' + idx);
        el.className = 'slot slot-active';
        el.innerHTML = buildSlotHtml(idx, s);
        bindSlotEvents(idx);
        loadSlotMessages(idx);
    }

    function deactivateSlot(idx) {
        var s = slots[idx];
        s.conversaId = null; s.chatId = null; s.chatJid = null;
        s.clienteNome = null; s.clienteNumero = null;
        s.lastMsgId = 0; s.hasNewMsg = false; s.isSending = false;
        s.replyingToKey = null; s.editingKey = null;

        var el = document.getElementById('slot' + idx);
        el.className = 'slot slot-empty';
        el.innerHTML = '<div class="slot-placeholder"><i class="fas fa-comment-slash"></i><span>Slot disponivel</span></div>';
        updateContador();
    }

    function buildSlotHtml(idx, s) {
        var initials = getInitials(s.clienteNome);
        var color = s.avatarColor;
        return ''
            // Header
            + '<div class="slot-header">'
            + '<div class="slot-avatar" style="background:' + color + ';">' + escapeHtml(initials) + '</div>'
            + '<div class="slot-header-info">'
            + '<div class="slot-client-name" title="' + escapeAttr(s.clienteNome) + '">' + escapeHtml(s.clienteNome) + ' <i class="fas fa-pen btn-edit-name" data-slot="' + idx + '" title="Editar nome" style="font-size:0.6rem;color:#999;cursor:pointer;"></i></div>'
            + '<div class="slot-client-number">' + escapeHtml(s.clienteNumero || s.chatJid || '') + '</div>'
            + '</div>'
            + '<div class="slot-header-actions">'
            + '<button class="btn-slot-action btn-slot-refresh" data-slot="' + idx + '" title="Recarregar"><i class="fas fa-sync-alt"></i></button>'
            + '<button class="btn-slot-action btn-slot-menu" data-slot="' + idx + '" title="Opcoes"><i class="fas fa-ellipsis-v"></i></button>'
            + '</div>'
            + '<div class="slot-dropdown" id="slotDrop' + idx + '">'
            + '<div class="drop-item text-warning btn-slot-devolver" data-slot="' + idx + '"><i class="fas fa-undo"></i> Devolver p/ Fila</div>'
            + '<div class="drop-item text-danger btn-slot-finalizar" data-slot="' + idx + '"><i class="fas fa-check-circle"></i> Finalizar</div>'
            + '</div>'
            + '</div>'
            // Messages
            + '<div class="slot-messages" id="slotMsgs' + idx + '">'
            + '<div class="slot-loading"><i class="fas fa-spinner"></i> Carregando...</div>'
            + '</div>'
            // Reply bar
            + '<div class="slot-reply-bar" id="slotReply' + idx + '">'
            + '<div class="reply-content"><div class="reply-sender" id="slotReplySender' + idx + '"></div>'
            + '<div class="reply-text" id="slotReplyText' + idx + '"></div></div>'
            + '<button class="btn-close-reply btn-cancel-reply" data-slot="' + idx + '"><i class="fas fa-times"></i></button>'
            + '</div>'
            // Input area
            + '<div class="slot-input-area">'
            + '<div class="slot-input-row">'
            + '<textarea class="slot-text" id="slotInput' + idx + '" placeholder="Digite..." rows="1"></textarea>'
            + '<button class="btn-slot-send" data-slot="' + idx + '" title="Enviar"><i class="fas fa-paper-plane"></i></button>'
            + '</div>'
            + '<div class="slot-actions-row">'
            + '<button class="btn-slot-tool btn-slot-attach" data-slot="' + idx + '" title="Anexar arquivo"><i class="fas fa-paperclip"></i></button>'
            + '<button class="btn-slot-tool btn-slot-mic" data-slot="' + idx + '" title="Audio"><i class="fas fa-microphone"></i></button>'
            + '</div>'
            + '</div>';
    }

    function bindSlotEvents(idx) {
        var el = document.getElementById('slot' + idx);

        // Send
        el.querySelector('.btn-slot-send').addEventListener('click', function() { sendSlotMessage(idx); });

        // Input
        var input = document.getElementById('slotInput' + idx);
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (this.value.trim()) sendSlotMessage(idx);
            }
        });
        input.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 70) + 'px';
        });
        input.addEventListener('focus', function() {
            document.getElementById('slot' + idx).classList.remove('slot-flash');
            slots[idx].hasNewMsg = false;
        });
        // Paste image from clipboard
        input.addEventListener('paste', function(e) {
            var items = (e.clipboardData || e.originalEvent.clipboardData).items;
            for (var i = 0; i < items.length; i++) {
                if (items[i].type.indexOf('image') !== -1) {
                    e.preventDefault();
                    var file = items[i].getAsFile();
                    if (file) {
                        showConfirm({
                            type: 'warn',
                            title: 'Enviar Imagem',
                            text: 'Enviar imagem colada do clipboard?',
                            okText: 'Enviar',
                            cancelText: 'Cancelar'
                        }, function(ok) {
                            if (ok) sendSlotMedia(idx, file);
                        });
                    }
                    return;
                }
            }
        });

        // Attach
        el.querySelector('.btn-slot-attach').addEventListener('click', function() {
            activeFileSlot = idx;
            document.getElementById('globalFileInput').click();
        });

        // Refresh
        el.querySelector('.btn-slot-refresh').addEventListener('click', function() {
            loadSlotMessages(idx);
            var ic = this.querySelector('i');
            ic.classList.add('fa-spin');
            setTimeout(function() { ic.classList.remove('fa-spin'); }, 800);
        });

        // Menu toggle
        el.querySelector('.btn-slot-menu').addEventListener('click', function(e) {
            e.stopPropagation();
            var drop = document.getElementById('slotDrop' + idx);
            // Close all other dropdowns
            document.querySelectorAll('.slot-dropdown.open').forEach(function(d) {
                if (d.id !== 'slotDrop' + idx) d.classList.remove('open');
            });
            drop.classList.toggle('open');
        });

        // Devolver
        el.querySelector('.btn-slot-devolver').addEventListener('click', function() {
            document.getElementById('slotDrop' + idx).classList.remove('open');
            var nome = slots[idx].clienteNome || 'esta conversa';
            showConfirm({
                type: 'warn',
                title: 'Devolver para a Fila',
                text: 'A conversa com ' + nome + ' voltara para a fila de espera.',
                okText: 'Devolver',
                cancelText: 'Cancelar'
            }, function(ok) {
                if (!ok) return;
                ajaxPost(devolverAjaxUrl + '?id=' + slots[idx].conversaId, function(resp) {
                    if (resp.success) deactivateSlot(idx);
                    else showConfirm({ type: 'danger', title: 'Erro', text: resp.error || 'Erro ao devolver.', okText: 'OK', cancelText: 'Fechar' }, function(){});
                });
            });
        });

        // Finalizar
        el.querySelector('.btn-slot-finalizar').addEventListener('click', function() {
            document.getElementById('slotDrop' + idx).classList.remove('open');
            var nome = slots[idx].clienteNome || 'esta conversa';
            showConfirm({
                type: 'danger',
                title: 'Finalizar Conversa',
                text: 'Tem certeza que deseja finalizar a conversa com ' + nome + '?',
                okText: 'Finalizar',
                cancelText: 'Cancelar'
            }, function(ok) {
                if (!ok) return;
                ajaxPost(finalizarAjaxUrl + '?id=' + slots[idx].conversaId, function(resp) {
                    if (resp.success) deactivateSlot(idx);
                    else showConfirm({ type: 'danger', title: 'Erro', text: resp.error || 'Erro ao finalizar.', okText: 'OK', cancelText: 'Fechar' }, function(){});
                });
            });
        });

        // Cancel reply
        var cancelBtn = el.querySelector('.btn-cancel-reply');
        if (cancelBtn) cancelBtn.addEventListener('click', function() { cancelSlotReply(idx); });

        // Messages: context menu
        var msgsContainer = document.getElementById('slotMsgs' + idx);
        msgsContainer.addEventListener('contextmenu', function(e) {
            var bubble = e.target.closest('.msg-bubble');
            if (!bubble) return;
            e.preventDefault();
            e.stopPropagation();
            contextTarget = bubble;
            contextSlotIdx = idx;
            openContextMenu(e.clientX, e.clientY, bubble);
        });

        // Messages: click delegation
        msgsContainer.addEventListener('click', function(e) {
            // Hover reply
            var hoverReply = e.target.closest('.hover-reply');
            if (hoverReply) {
                var bubble = hoverReply.closest('.msg-bubble');
                var msgKey = bubble.getAttribute('data-message-key');
                var msgText = bubble.getAttribute('data-message-text');
                var senderEl = bubble.querySelector('.msg-sender');
                var senderName = senderEl ? senderEl.textContent : (bubble.getAttribute('data-is-from-me') === '1' ? 'Voce' : '');
                startSlotReply(idx, msgKey, msgText, senderName);
                return;
            }
            // Hover react
            var hoverReact = e.target.closest('.hover-react');
            if (hoverReact) {
                var bubble = hoverReact.closest('.msg-bubble');
                openEmojiPicker(bubble, idx);
                return;
            }
            // Hover edit
            var hoverEdit = e.target.closest('.hover-edit');
            if (hoverEdit) {
                var bubble = hoverEdit.closest('.msg-bubble');
                var msgKey = bubble.getAttribute('data-message-key');
                var msgText = bubble.getAttribute('data-message-text');
                startSlotEdit(idx, msgKey, msgText);
                return;
            }
            // Hover delete
            var hoverDelete = e.target.closest('.hover-delete');
            if (hoverDelete) {
                var bubble = hoverDelete.closest('.msg-bubble');
                var msgKey = bubble.getAttribute('data-message-key');
                confirmDeleteMessage(idx, msgKey);
                return;
            }
            // Reaction chip
            var chip = e.target.closest('.msg-reaction-chip');
            if (chip) {
                sendReaction(idx, chip.getAttribute('data-message-key'), chip.getAttribute('data-emoji'));
                return;
            }
            // Quoted scroll
            var quoted = e.target.closest('.msg-quoted');
            if (quoted) {
                var qKey = quoted.getAttribute('data-quoted-key');
                var target = msgsContainer.querySelector('.msg-bubble[data-message-key="' + qKey + '"]');
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    target.style.background = '#ffefc2';
                    setTimeout(function() { target.style.background = ''; }, 1500);
                }
            }
        });
    }

    // Close dropdowns on outside click
    document.addEventListener('click', function() {
        document.querySelectorAll('.slot-dropdown.open').forEach(function(d) { d.classList.remove('open'); });
    });

    // ==================== MESSAGES ====================
    function loadSlotMessages(idx) {
        var s = slots[idx];
        if (!s.chatId) return;
        var container = document.getElementById('slotMsgs' + idx);
        container.innerHTML = '<div class="slot-loading"><i class="fas fa-spinner"></i> Carregando...</div>';

        ajaxGet(messagesUrl + '?chat_id=' + s.chatId, function(resp) {
            if (!resp.success || findSlotByChatId(s.chatId) !== idx) return;
            if (!resp.messages || resp.messages.length === 0) {
                container.innerHTML = '<div class="slot-loading" style="color:#bbb;">Nenhuma mensagem ainda.</div>';
                return;
            }
            renderSlotMessages(container, resp.messages, idx);
            var last = resp.messages[resp.messages.length - 1];
            if (last) {
                slots[idx].lastMsgId = last.id;
                slots[idx].lastTimestamp = last.timestamp || 0;
            }
        });
    }

    function pollSlotMessages(idx) {
        var s = slots[idx];
        if (!s.chatId || !s.lastMsgId) return;

        ajaxGet(messagesUrl + '?chat_id=' + s.chatId + '&after_id=' + s.lastMsgId, function(resp) {
            if (!resp.success || !resp.messages || resp.messages.length === 0) return;
            if (findSlotByChatId(s.chatId) !== idx) return;

            // Verificar se alguma mensagem tem timestamp mais antigo que a ultima renderizada
            // Isso acontece com insercoes manuais ou sync - nesse caso, recarregar tudo
            var hasOldTimestamp = false;
            if (s.lastTimestamp > 0) {
                for (var i = 0; i < resp.messages.length; i++) {
                    if (resp.messages[i].timestamp && resp.messages[i].timestamp < s.lastTimestamp) {
                        hasOldTimestamp = true;
                        break;
                    }
                }
            }

            if (hasOldTimestamp) {
                // Reload completo para ordenacao correta
                loadSlotMessages(idx);
                return;
            }

            var container = document.getElementById('slotMsgs' + idx);
            if (!container) return;
            var wasAtBottom = (container.scrollHeight - container.scrollTop - container.clientHeight) < 50;
            appendSlotMessages(container, resp.messages, idx);
            var last = resp.messages[resp.messages.length - 1];
            if (last) {
                slots[idx].lastMsgId = last.id;
                slots[idx].lastTimestamp = last.timestamp || s.lastTimestamp;
            }
            if (wasAtBottom) container.scrollTop = container.scrollHeight;

            var input = document.getElementById('slotInput' + idx);
            if (document.activeElement !== input) {
                document.getElementById('slot' + idx).classList.add('slot-flash');
                slots[idx].hasNewMsg = true;
            }
        });
    }

    function renderSlotMessages(container, messages, idx) {
        var html = '';
        var lastDate = '';
        for (var i = 0; i < messages.length; i++) {
            html += buildMsgHtml(messages[i], lastDate, idx);
            if (messages[i].date_formatted) lastDate = messages[i].date_formatted;
        }
        container.innerHTML = html;
        container.scrollTop = container.scrollHeight;
    }

    function appendSlotMessages(container, messages, idx) {
        // Deduplicar: ignorar mensagens que ja existem no DOM (por message_key)
        var existingKeys = {};
        container.querySelectorAll('.msg-bubble[data-message-key]').forEach(function(el) {
            if (el.dataset.messageKey) existingKeys[el.dataset.messageKey] = true;
        });
        var newMessages = [];
        for (var i = 0; i < messages.length; i++) {
            if (!messages[i].message_key || !existingKeys[messages[i].message_key]) {
                newMessages.push(messages[i]);
            }
        }
        if (newMessages.length === 0) return;

        var lastDateEls = container.querySelectorAll('.msg-date-separator span');
        var lastDate = lastDateEls.length ? lastDateEls[lastDateEls.length - 1].textContent : '';
        var html = '';
        for (var i = 0; i < newMessages.length; i++) {
            html += buildMsgHtml(newMessages[i], lastDate, idx);
            if (newMessages[i].date_formatted) lastDate = newMessages[i].date_formatted;
        }
        container.insertAdjacentHTML('beforeend', html);
    }

    function buildMsgHtml(msg, lastDate, slotIdx) {
        var h = '';
        if (msg.date_formatted && msg.date_formatted !== lastDate) {
            h += '<div class="msg-clearfix"></div><div class="msg-date-separator"><span>' + escapeHtml(msg.date_formatted) + '</span></div>';
        }

        var cls = msg.is_from_me ? 'sent' : 'received';
        var s = slots[slotIdx];
        var badgeColor = msg.is_from_me ? '#0984e3' : (s ? s.avatarColor : '#e17055');
        var isGroup = s && s.chatJid && s.chatJid.indexOf('@g.us') !== -1;
        var badgeInitials = msg.is_from_me
            ? (msg.sent_by_user_name ? getInitials(msg.sent_by_user_name) : 'EU')
            : getInitials(isGroup ? (msg.sender_name || msg.from_jid || '') : (s ? s.clienteNome : '') || '');

        // Msg row with avatar badge
        h += '<div class="msg-row ' + cls + '">';
        h += '<div class="msg-badge" style="background:' + badgeColor + ';">' + escapeHtml(badgeInitials) + '</div>';

        h += '<div class="msg-bubble ' + cls + '"'
            + ' data-message-key="' + escapeAttr(msg.message_key || '') + '"'
            + ' data-message-id="' + msg.id + '"'
            + ' data-is-from-me="' + (msg.is_from_me ? '1' : '0') + '"'
            + ' data-message-text="' + escapeAttr(msg.message_text || '') + '"'
            + ' data-slot="' + slotIdx + '">';

        // Hover actions (reply, react, edit, delete)
        if (!msg.is_deleted) {
            h += '<div class="msg-hover-actions">';
            h += '<span class="hover-btn hover-reply" title="Responder"><i class="fas fa-reply"></i></span>';
            h += '<span class="hover-btn hover-react" title="Reagir"><i class="fas fa-smile"></i></span>';
            if (msg.is_from_me && msg.message_text) {
                h += '<span class="hover-btn hover-edit" title="Editar"><i class="fas fa-pen"></i></span>';
            }
            if (msg.is_from_me) {
                h += '<span class="hover-btn hover-delete" title="Excluir"><i class="fas fa-trash-alt"></i></span>';
            }
            h += '</div>';
        }

        // Sender (only in group chats)
        if (msg.from_jid && !msg.is_from_me && s && s.chatJid && s.chatJid.indexOf('@g.us') !== -1) {
            h += '<div class="msg-sender">' + escapeHtml(msg.sender_name || msg.from_jid.replace(/@.*/, '')) + '</div>';
        }

        // Deleted message
        if (msg.is_deleted) {
            h += '<div class="msg-deleted-text"><i class="fas fa-ban"></i> Mensagem apagada</div>';
        } else {
            // Quoted
            if (msg.quoted_message_id) {
                h += '<div class="msg-quoted" data-quoted-key="' + escapeAttr(msg.quoted_message_id) + '">';
                h += '<div class="quoted-text">' + escapeHtml(msg.quoted_text || 'Mensagem') + '</div></div>';
            }

            // Media
            if (msg.message_type && msg.message_type !== 'text') h += renderMedia(msg);
            // Text
            if (msg.message_text) h += '<div class="msg-text">' + formatText(msg.message_text) + '</div>';
        }

        // Meta
        h += '<div class="msg-meta">';
        if (msg.sent_by_user_name) h += '<span class="msg-sender-name">' + escapeHtml(msg.sent_by_user_name) + '</span>';
        if (msg.is_edited) h += '<span class="msg-edited-label">editado</span>';
        h += '<span>' + escapeHtml(msg.time_formatted || '') + '</span>';
        if (msg.is_from_me && msg.status) {
            var si = '', sc = '';
            if (msg.status === 'read') { si = 'fa-check-double'; sc = 'read'; }
            else if (msg.status === 'delivered') { si = 'fa-check-double'; sc = 'delivered'; }
            else if (msg.status === 'sent') { si = 'fa-check'; sc = ''; }
            else if (msg.status === 'pending') { si = 'fa-clock'; sc = ''; }
            if (si) h += ' <span class="msg-status ' + sc + '"><i class="fas ' + si + '"></i></span>';
        }
        h += '</div>';

        // Reactions
        if (msg.reactions && msg.reactions.length > 0) h += renderReactions(msg.reactions, msg.message_key);

        h += '</div>'; // msg-bubble
        h += '</div>'; // msg-row
        return h;
    }

    function renderMedia(msg) {
        var h = '<div class="msg-media">';
        var t = msg.message_type, url = msg.media_url;
        if (t === 'image' && url) h += '<img src="' + escapeAttr(url) + '" class="media-image" onclick="window.open(this.src)" title="Ampliar">';
        else if (t === 'sticker' && url) h += '<img src="' + escapeAttr(url) + '" class="media-sticker">';
        else if (t === 'audio' && url) h += '<audio controls class="media-audio"><source src="' + escapeAttr(url) + '"></audio>';
        else if (t === 'video' && url) h += '<video controls class="media-video"><source src="' + escapeAttr(url) + '"></video>';
        else if (t === 'document' && url) {
            h += '<a href="' + escapeAttr(url) + '" target="_blank" class="media-doc"><i class="fas fa-file-alt"></i> ' + escapeHtml(msg.message_text || 'Documento') + '</a>';
        } else if (t === 'location') h += '<div class="media-icon"><i class="fas fa-map-marker-alt"></i> Local</div>';
        else if (t === 'contact') h += '<div class="media-icon"><i class="fas fa-address-card"></i> Contato</div>';
        else {
            var icons = {image:'fa-image',audio:'fa-microphone',video:'fa-video',document:'fa-file-alt',sticker:'fa-sticky-note'};
            h += '<div class="media-icon"><i class="fas ' + (icons[t]||'fa-paperclip') + '"></i> ' + escapeHtml({image:'Imagem',audio:'Audio',video:'Video',document:'Documento',sticker:'Sticker'}[t]||t) + '</div>';
        }
        h += '</div>';
        return h;
    }

    function renderReactions(reactions, messageKey) {
        var grouped = {};
        for (var i = 0; i < reactions.length; i++) {
            var r = reactions[i];
            if (!grouped[r.emoji]) grouped[r.emoji] = [];
            grouped[r.emoji].push(r.senderJid);
        }
        var h = '<div class="msg-reactions">';
        for (var emoji in grouped) {
            h += '<span class="msg-reaction-chip" data-message-key="' + escapeAttr(messageKey) + '" data-emoji="' + escapeAttr(emoji) + '">';
            h += emoji;
            if (grouped[emoji].length > 1) h += ' <span class="reaction-count">' + grouped[emoji].length + '</span>';
            h += '</span>';
        }
        h += '</div>';
        return h;
    }

    // ==================== SEND ====================
    function sendSlotMessage(idx) {
        var s = slots[idx];
        var input = document.getElementById('slotInput' + idx);
        var text = input.value.trim();
        if (!text || !s.chatId || s.isSending) return;

        if (s.editingKey) { sendSlotEdit(idx, s.editingKey, text); return; }

        s.isSending = true;
        input.value = '';
        input.style.height = 'auto';

        var postData = 'chat_id=' + encodeURIComponent(s.chatId)
            + '&text=' + encodeURIComponent(text)
            + '&_csrf-backend=' + encodeURIComponent(csrfToken);
        if (s.replyingToKey) postData += '&quoted_message_key=' + encodeURIComponent(s.replyingToKey);
        cancelSlotReply(idx);

        ajaxPostRaw(sendMessageUrl, postData, function(resp) {
            s.isSending = false;
            input.focus();
            if (resp.success) setTimeout(function() { loadSlotMessages(idx); }, 600);
        });
    }

    function sendSlotMedia(idx, file) {
        var s = slots[idx];
        if (s.isSending || !s.chatId) return;
        s.isSending = true;
        var input = document.getElementById('slotInput' + idx);
        var caption = input.value.trim();
        input.value = ''; input.style.height = 'auto';

        var fd = new FormData();
        fd.append('chat_id', s.chatId);
        fd.append('file', file);
        fd.append('caption', caption);
        fd.append('_csrf-backend', csrfToken);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', sendMediaUrl, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            s.isSending = false;
            if (xhr.status === 200) {
                try {
                    var resp = JSON.parse(xhr.responseText);
                    if (resp.success) setTimeout(function() { loadSlotMessages(idx); }, 600);
                    else showConfirm({ type: 'danger', title: 'Erro', text: resp.error || 'Erro desconhecido ao enviar.', okText: 'OK', cancelText: 'Fechar' }, function(){});
                } catch(e) { showConfirm({ type: 'danger', title: 'Erro', text: 'Erro ao processar resposta.', okText: 'OK', cancelText: 'Fechar' }, function(){}); }
            } else showConfirm({ type: 'danger', title: 'Erro', text: 'Erro HTTP ' + xhr.status, okText: 'OK', cancelText: 'Fechar' }, function(){});
        };
        xhr.send(fd);
    }

    function sendSlotEdit(idx, messageKey, newText) {
        var s = slots[idx];
        s.isSending = true;
        var input = document.getElementById('slotInput' + idx);
        input.value = ''; input.style.height = 'auto';
        cancelSlotReply(idx);
        s.editingKey = null; s.editingText = null;

        ajaxPostRaw(editMessageUrl,
            'chat_id=' + encodeURIComponent(s.chatId)
            + '&message_key=' + encodeURIComponent(messageKey)
            + '&new_text=' + encodeURIComponent(newText)
            + '&_csrf-backend=' + encodeURIComponent(csrfToken),
            function(resp) {
                s.isSending = false; input.focus();
                if (resp.success) setTimeout(function() { loadSlotMessages(idx); }, 500);
                else showConfirm({ type: 'danger', title: 'Erro', text: 'Erro ao editar: ' + (resp.error || ''), okText: 'OK', cancelText: 'Fechar' }, function(){});
            }
        );
    }

    function sendReaction(idx, messageKey, emoji) {
        var s = slots[idx];
        if (!s.chatId) return;
        ajaxPostRaw(reactMessageUrl,
            'chat_id=' + encodeURIComponent(s.chatId)
            + '&message_key=' + encodeURIComponent(messageKey)
            + '&emoji=' + encodeURIComponent(emoji)
            + '&_csrf-backend=' + encodeURIComponent(csrfToken),
            function() { setTimeout(function() { loadSlotMessages(idx); }, 500); }
        );
    }

    function sendSlotDelete(idx, messageKey) {
        var s = slots[idx];
        if (!s.chatId) return;
        ajaxPostRaw(deleteMessageUrl,
            'chat_id=' + encodeURIComponent(s.chatId)
            + '&message_key=' + encodeURIComponent(messageKey)
            + '&_csrf-backend=' + encodeURIComponent(csrfToken),
            function(resp) {
                if (resp.success) setTimeout(function() { loadSlotMessages(idx); }, 500);
                else showConfirm({ type: 'danger', title: 'Erro', text: resp.error || 'Erro ao excluir mensagem.', okText: 'OK', cancelText: 'Fechar' }, function(){});
            }
        );
    }

    function confirmDeleteMessage(idx, messageKey) {
        showConfirm({
            type: 'danger',
            title: 'Excluir Mensagem',
            text: 'Deseja excluir esta mensagem para todos?',
            okText: 'Excluir',
            cancelText: 'Cancelar'
        }, function(ok) {
            if (ok) sendSlotDelete(idx, messageKey);
        });
    }

    // ==================== EMOJI PICKER ====================
    var emojiPicker = document.getElementById('emojiPicker');
    var emojiTargetBubble = null;
    var emojiTargetSlot = null;

    function openEmojiPicker(bubble, slotIdx) {
        emojiTargetBubble = bubble;
        emojiTargetSlot = slotIdx;
        var rect = bubble.getBoundingClientRect();
        var pickerW = 220, pickerH = 100;
        var posX = rect.left + rect.width / 2 - pickerW / 2;
        var posY = rect.top - pickerH - 8;
        if (posY < 10) posY = rect.bottom + 8;
        if (posX + pickerW > window.innerWidth - 10) posX = window.innerWidth - pickerW - 10;
        if (posX < 10) posX = 10;
        emojiPicker.style.left = posX + 'px';
        emojiPicker.style.top = posY + 'px';
        emojiPicker.style.display = 'block';
    }

    document.addEventListener('click', function(e) {
        if (!emojiPicker.contains(e.target) && !e.target.closest('.hover-react')) {
            emojiPicker.style.display = 'none';
        }
    });

    // Emoji tabs
    emojiPicker.querySelectorAll('.emoji-tab').forEach(function(tab) {
        tab.addEventListener('click', function(e) {
            e.stopPropagation();
            var panelName = this.getAttribute('data-panel');
            emojiPicker.querySelectorAll('.emoji-tab').forEach(function(t) { t.classList.remove('active'); });
            emojiPicker.querySelectorAll('.emoji-panel').forEach(function(p) { p.classList.remove('active'); });
            this.classList.add('active');
            var panel = emojiPicker.querySelector('.emoji-panel[data-panel="' + panelName + '"]');
            if (panel) panel.classList.add('active');
        });
    });

    emojiPicker.querySelectorAll('.emoji-option').forEach(function(opt) {
        opt.addEventListener('click', function(e) {
            e.stopPropagation();
            emojiPicker.style.display = 'none';
            if (!emojiTargetBubble || emojiTargetSlot === null) return;
            var msgKey = emojiTargetBubble.getAttribute('data-message-key');
            sendReaction(emojiTargetSlot, msgKey, this.getAttribute('data-emoji'));
        });
    });

    // ==================== FORWARD ====================
    var forwardOverlay = document.getElementById('forwardOverlay');
    var forwardList = document.getElementById('forwardList');
    var forwardMsgKey = null;
    var forwardFromSlot = null;

    document.getElementById('forwardCancel').addEventListener('click', function() {
        forwardOverlay.classList.remove('open');
    });
    forwardOverlay.addEventListener('click', function(e) {
        if (e.target === forwardOverlay) forwardOverlay.classList.remove('open');
    });

    function openForwardModal(fromSlotIdx, messageKey) {
        forwardMsgKey = messageKey;
        forwardFromSlot = fromSlotIdx;
        var html = '';
        var hasTargets = false;
        for (var i = 0; i < MAX_SLOTS; i++) {
            if (i === fromSlotIdx || !slots[i].conversaId) continue;
            hasTargets = true;
            var s = slots[i];
            var color = s.avatarColor || '#999';
            var initials = getInitials(s.clienteNome);
            html += '<div class="forward-item" data-slot="' + i + '">'
                + '<div class="fw-avatar" style="background:' + color + ';">' + escapeHtml(initials) + '</div>'
                + '<div class="fw-name">' + escapeHtml(s.clienteNome || s.clienteNumero || 'Chat ' + (i+1)) + '</div>'
                + '</div>';
        }
        if (!hasTargets) {
            html = '<div class="forward-empty">Nenhuma outra conversa aberta para encaminhar.</div>';
        }
        forwardList.innerHTML = html;

        forwardList.querySelectorAll('.forward-item').forEach(function(item) {
            item.addEventListener('click', function() {
                var targetSlot = parseInt(this.getAttribute('data-slot'));
                forwardOverlay.classList.remove('open');
                sendForwardMessage(forwardFromSlot, targetSlot, forwardMsgKey);
            });
        });

        forwardOverlay.classList.add('open');
    }

    function sendForwardMessage(fromSlotIdx, toSlotIdx, messageKey) {
        var fromS = slots[fromSlotIdx];
        var toS = slots[toSlotIdx];
        if (!fromS.chatId || !toS.chatId) return;

        ajaxPostRaw(forwardMessageUrl,
            'from_chat_id=' + encodeURIComponent(fromS.chatId)
            + '&to_chat_id=' + encodeURIComponent(toS.chatId)
            + '&message_key=' + encodeURIComponent(messageKey)
            + '&_csrf-backend=' + encodeURIComponent(csrfToken),
            function(resp) {
                if (resp.success) {
                    setTimeout(function() { loadSlotMessages(toSlotIdx); }, 600);
                } else {
                    showConfirm({ type: 'danger', title: 'Erro', text: resp.error || 'Erro ao encaminhar.', okText: 'OK', cancelText: 'Fechar' }, function(){});
                }
            }
        );
    }

    // ==================== REPLY / EDIT ====================
    function startSlotReply(idx, msgKey, text, senderName) {
        var s = slots[idx];
        s.replyingToKey = msgKey; s.replyingToText = text; s.replyingToSender = senderName;
        s.editingKey = null;
        var bar = document.getElementById('slotReply' + idx);
        document.getElementById('slotReplySender' + idx).textContent = senderName || '';
        document.getElementById('slotReplyText' + idx).textContent = (text || '').substring(0, 60);
        bar.querySelector('.reply-content').style.borderLeftColor = '#06cf9c';
        bar.style.display = 'flex';
        document.getElementById('slotInput' + idx).focus();
    }

    function startSlotEdit(idx, msgKey, text) {
        var s = slots[idx];
        s.editingKey = msgKey; s.editingText = text; s.replyingToKey = null;
        var input = document.getElementById('slotInput' + idx);
        input.value = text || ''; input.focus();
        var bar = document.getElementById('slotReply' + idx);
        document.getElementById('slotReplySender' + idx).textContent = 'Editando';
        document.getElementById('slotReplyText' + idx).textContent = (text || '').substring(0, 60);
        bar.querySelector('.reply-content').style.borderLeftColor = '#f59e0b';
        bar.style.display = 'flex';
    }

    function cancelSlotReply(idx) {
        var s = slots[idx];
        if (s.editingKey) {
            s.editingKey = null; s.editingText = null;
            var input = document.getElementById('slotInput' + idx);
            if (input) input.value = '';
        }
        s.replyingToKey = null; s.replyingToText = null; s.replyingToSender = null;
        var bar = document.getElementById('slotReply' + idx);
        if (bar) {
            bar.style.display = 'none';
            var c = bar.querySelector('.reply-content');
            if (c) c.style.borderLeftColor = '#06cf9c';
        }
    }

    // ==================== CONTEXT MENU ====================
    var contextMenu = document.getElementById('msgContextMenu');

    function openContextMenu(posX, posY, bubble) {
        var isFromMe = bubble.getAttribute('data-is-from-me') === '1';
        var hasText = bubble.getAttribute('data-message-text') && bubble.getAttribute('data-message-text').length > 0;
        var isDeleted = bubble.querySelector('.msg-deleted-text') !== null;
        if (isDeleted) return; // no actions on deleted messages
        contextMenu.querySelector('.ctx-edit-only').style.display = (isFromMe && hasText) ? '' : 'none';
        contextMenu.querySelector('.ctx-delete-only').style.display = isFromMe ? '' : 'none';
        contextMenu.style.display = 'block';
        if (posX + 160 > window.innerWidth) posX = window.innerWidth - 170;
        if (posY + 160 > window.innerHeight) posY = window.innerHeight - 170;
        contextMenu.style.left = Math.max(10, posX) + 'px';
        contextMenu.style.top = Math.max(10, posY) + 'px';
    }

    document.addEventListener('click', function(e) {
        if (!contextMenu.contains(e.target)) contextMenu.style.display = 'none';
    });

    contextMenu.querySelectorAll('.ctx-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.stopPropagation();
            contextMenu.style.display = 'none';
            if (!contextTarget || contextSlotIdx === null) return;
            var action = this.getAttribute('data-action');
            var msgKey = contextTarget.getAttribute('data-message-key');
            var msgText = contextTarget.getAttribute('data-message-text');
            var senderEl = contextTarget.querySelector('.msg-sender');
            var senderName = senderEl ? senderEl.textContent : (contextTarget.getAttribute('data-is-from-me') === '1' ? 'Voce' : '');
            if (action === 'reply') startSlotReply(contextSlotIdx, msgKey, msgText, senderName);
            else if (action === 'edit') startSlotEdit(contextSlotIdx, msgKey, msgText);
            else if (action === 'copy') navigator.clipboard.writeText(msgText || '');
            else if (action === 'react') openEmojiPicker(contextTarget, contextSlotIdx);
            else if (action === 'forward') openForwardModal(contextSlotIdx, msgKey);
            else if (action === 'delete') confirmDeleteMessage(contextSlotIdx, msgKey);
        });
    });

    // ==================== FILE INPUT ====================
    document.getElementById('globalFileInput').addEventListener('change', function() {
        if (!this.files || !this.files[0] || activeFileSlot === null) return;
        sendSlotMedia(activeFileSlot, this.files[0]);
        this.value = '';
        activeFileSlot = null;
    });

    // ==================== FILA DRAWER ====================
    document.getElementById('btnAbrirFila').addEventListener('click', function() {
        document.getElementById('filaDrawer').classList.add('open');
        document.getElementById('filaOverlay').classList.add('open');
        refreshFila();
    });
    document.getElementById('btnFecharFila').addEventListener('click', closeFila);
    document.getElementById('filaOverlay').addEventListener('click', closeFila);

    function closeFila() {
        document.getElementById('filaDrawer').classList.remove('open');
        document.getElementById('filaOverlay').classList.remove('open');
    }

    function refreshFila() {
        ajaxGet(filaJsonUrl, function(resp) {
            if (!resp.success) return;
            var container = document.getElementById('filaCards');
            var count = resp.count || 0;
            document.getElementById('filaDrawerCount').textContent = count;
            document.getElementById('filaBadge').textContent = count;
            var btnFila = document.getElementById('btnAbrirFila');
            if (count > 0) btnFila.classList.add('has-waiting');
            else btnFila.classList.remove('has-waiting');

            if (count === 0) {
                container.innerHTML = '<div class="fila-empty"><i class="fas fa-check-circle"></i><span>Nenhuma conversa na fila.</span></div>';
                return;
            }

            var emptySlots = 0;
            for (var i = 0; i < MAX_SLOTS; i++) { if (!slots[i].conversaId) emptySlots++; }

            var html = '';
            for (var i = 0; i < resp.conversas.length; i++) {
                var c = resp.conversas[i];
                var color = getAvatarColor(c.cliente_nome);
                var initials = getInitials(c.cliente_nome);
                html += '<div class="fila-card">'
                    + '<div class="d-flex align-items-center" style="gap:10px;">'
                    + '<div class="slot-avatar" style="background:' + color + ';width:40px;height:40px;font-size:0.8rem;">' + escapeHtml(initials) + '</div>'
                    + '<div style="flex:1;min-width:0;">'
                    + '<div class="fila-card-nome">' + escapeHtml(c.cliente_nome) + '</div>'
                    + '<div class="fila-card-numero">' + escapeHtml(c.cliente_numero) + '</div>'
                    + '</div></div>'
                    + (c.preview ? '<div class="fila-card-preview"><i class="fas fa-comment-dots"></i> ' + escapeHtml(c.preview) + '</div>' : '')
                    + '<div class="fila-card-footer">'
                    + '<span class="fila-card-tempo"><i class="fas fa-clock"></i> ' + escapeHtml(c.tempo_fila) + '</span>'
                    + '<button class="btn-aceitar" data-conversa-id="' + c.id + '"'
                    + (emptySlots <= 0 ? ' disabled title="Todos os slots ocupados"' : '')
                    + '><i class="fas fa-hand-pointer"></i> Aceitar</button>'
                    + '</div></div>';
            }
            container.innerHTML = html;

            container.querySelectorAll('.btn-aceitar').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    aceitarConversa(this.getAttribute('data-conversa-id'));
                });
            });
        });
    }

    function aceitarConversa(conversaId) {
        var idx = findEmptySlot();
        if (idx === -1) {
            showConfirm({ type: 'warn', title: 'Slots Cheios', text: 'Todos os 8 slots estao ocupados. Finalize uma conversa primeiro.', okText: 'Entendi', cancelText: 'Fechar' }, function(){});
            return;
        }

        ajaxPost(pegarAjaxUrl + '?id=' + conversaId, function(resp) {
            if (!resp.success) {
                showConfirm({ type: 'danger', title: 'Erro', text: resp.error || 'Erro ao aceitar conversa.', okText: 'OK', cancelText: 'Fechar' }, function(){});
                refreshFila();
                return;
            }
            activateSlot(idx, resp);
            updateContador();
            refreshFila();
        });
    }

    // ==================== POLLING ====================
    function startPolling() {
        msgPollTimer = setInterval(function() {
            for (var i = 0; i < MAX_SLOTS; i++) {
                if (slots[i].chatId) pollSlotMessages(i);
            }
        }, 3000);

        filaPollTimer = setInterval(function() {
            ajaxGet(filaJsonUrl, function(resp) {
                if (!resp.success) return;
                var count = resp.count || 0;
                document.getElementById('filaBadge').textContent = count;
                document.getElementById('filaDrawerCount').textContent = count;
                var btnFila = document.getElementById('btnAbrirFila');
                if (count > 0) btnFila.classList.add('has-waiting');
                else btnFila.classList.remove('has-waiting');
            });
        }, 5000);
    }

    function updateContador() {
        var active = 0;
        for (var i = 0; i < MAX_SLOTS; i++) { if (slots[i].conversaId) active++; }
        var el = document.getElementById('contadorConversas');
        if (el) el.textContent = active;
    }

    // ==================== INIT ====================
    function initDashboard() {
        ajaxGet(minhasConversasUrl, function(resp) {
            if (!resp.success) return;
            var conversas = resp.conversas || [];
            for (var i = 0; i < conversas.length && i < MAX_SLOTS; i++) {
                activateSlot(i, conversas[i]);
            }
            updateContador();
        });
        startPolling();
    }

    document.getElementById('btnRefresh').addEventListener('click', function() {
        var icon = this.querySelector('i');
        icon.classList.add('fa-spin');
        setTimeout(function() { icon.classList.remove('fa-spin'); }, 1000);
        for (var i = 0; i < MAX_SLOTS; i++) { if (slots[i].chatId) loadSlotMessages(i); }
        refreshFila();
    });

    // ==================== AJAX HELPERS ====================
    function ajaxGet(url, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status === 200) { try { callback(JSON.parse(xhr.responseText)); } catch(e) {} }
        };
        xhr.send();
    }
    function ajaxPost(url, callback) {
        ajaxPostRaw(url, '_csrf-backend=' + encodeURIComponent(csrfToken), callback);
    }
    function ajaxPostRaw(url, data, callback) {
        var xhr = new XMLHttpRequest();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.onreadystatechange = function() {
            if (xhr.readyState !== 4) return;
            if (xhr.status === 200) { try { callback(JSON.parse(xhr.responseText)); } catch(e) {} }
            else { try { callback({success:false,error:'HTTP '+xhr.status}); } catch(e) {} }
        };
        xhr.send(data);
    }

    // ==================== MODAL CONFIRM ====================
    var modalOverlay = document.getElementById('modalConfirm');
    var modalIconEl = document.getElementById('modalIcon');
    var modalTitleEl = document.getElementById('modalTitle');
    var modalTextEl = document.getElementById('modalText');
    var modalOkBtn = document.getElementById('modalOk');
    var modalCancelBtn = document.getElementById('modalCancel');
    var modalCallback = null;

    function showConfirm(opts, callback) {
        var type = opts.type || 'warn'; // 'warn' or 'danger'
        var icon = type === 'danger' ? 'fa-exclamation-triangle' : 'fa-question-circle';
        modalIconEl.className = 'modal-confirm-icon ' + type;
        modalIconEl.innerHTML = '<i class="fas ' + icon + '"></i>';
        modalTitleEl.textContent = opts.title || 'Confirmar';
        modalTextEl.textContent = opts.text || 'Tem certeza?';
        modalOkBtn.textContent = opts.okText || 'Confirmar';
        modalOkBtn.className = 'btn-modal btn-modal-ok ' + type;
        modalCancelBtn.textContent = opts.cancelText || 'Cancelar';
        modalCallback = callback;
        modalOverlay.classList.add('open');
    }

    modalOkBtn.addEventListener('click', function() {
        modalOverlay.classList.remove('open');
        if (modalCallback) modalCallback(true);
        modalCallback = null;
    });
    modalCancelBtn.addEventListener('click', function() {
        modalOverlay.classList.remove('open');
        if (modalCallback) modalCallback(false);
        modalCallback = null;
    });
    modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) {
            modalOverlay.classList.remove('open');
            if (modalCallback) modalCallback(false);
            modalCallback = null;
        }
    });

    // ==================== UTILS ====================
    function formatText(text) {
        text = escapeHtml(text);
        text = text.replace(/(https?:\/\/[^\s<]+)/g, '<a href="$1" target="_blank" rel="noopener" style="color:#027eb5;font-size:inherit;">$1</a>');
        text = text.replace(/\n/g, '<br>');
        text = text.replace(/\*([^*]+)\*/g, '<strong>$1</strong>');
        text = text.replace(/_([^_]+)_/g, '<em>$1</em>');
        return text;
    }
    function escapeHtml(text) {
        if (!text) return '';
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(text));
        return d.innerHTML;
    }
    function escapeAttr(text) {
        if (!text) return '';
        return text.replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    // ==================== FINALIZAR TODAS ====================
    document.getElementById('btnFinalizarTodas').addEventListener('click', function() {
        var activeSlots = [];
        for (var i = 0; i < MAX_SLOTS; i++) {
            if (slots[i].conversaId) activeSlots.push(i);
        }
        if (activeSlots.length === 0) {
            showConfirm({ type: 'warn', title: 'Nenhuma Conversa', text: 'Nao ha conversas ativas para finalizar.', okText: 'OK', cancelText: 'Fechar' }, function(){});
            return;
        }
        showConfirm({
            type: 'danger',
            title: 'Finalizar Todas',
            text: 'Tem certeza que deseja finalizar ' + activeSlots.length + ' conversa(s) ativa(s)?',
            okText: 'Finalizar Todas',
            cancelText: 'Cancelar'
        }, function(ok) {
            if (!ok) return;
            var pending = activeSlots.length;
            var finalized = 0;
            activeSlots.forEach(function(idx) {
                ajaxPost(finalizarAjaxUrl + '?id=' + slots[idx].conversaId, function(resp) {
                    if (resp.success) {
                        deactivateSlot(idx);
                        finalized++;
                    }
                    pending--;
                    if (pending === 0) {
                        showConfirm({ type: 'warn', title: 'Concluido', text: finalized + ' conversa(s) finalizada(s).', okText: 'OK', cancelText: 'Fechar' }, function(){});
                        refreshFila();
                    }
                });
            });
        });
    });

    // Edit contact name (event delegation)
    document.addEventListener('click', function(e) {
        var btn = e.target.closest('.btn-edit-name');
        if (!btn) return;
        e.stopPropagation();
        var idx = parseInt(btn.getAttribute('data-slot'));
        var s = slots[idx];
        if (!s || !s.chatId) return;
        var novoNome = prompt('Editar nome do contato:', s.clienteNome || '');
        if (novoNome === null || novoNome.trim() === '') return;
        novoNome = novoNome.trim();
        var postData = '_csrf-backend=' + encodeURIComponent(csrfToken)
            + '&chat_id=' + encodeURIComponent(s.chatId)
            + '&name=' + encodeURIComponent(novoNome);
        ajaxPostRaw(updateContactNameUrl, postData, function(resp) {
            if (resp.success) {
                s.clienteNome = novoNome;
                s.avatarColor = getAvatarColor(novoNome);
                var el = document.getElementById('slot' + idx);
                var nameEl = el.querySelector('.slot-client-name');
                nameEl.title = novoNome;
                nameEl.innerHTML = escapeHtml(novoNome) + ' <i class="fas fa-pen btn-edit-name" data-slot="' + idx + '" title="Editar nome" style="font-size:0.6rem;color:#999;cursor:pointer;"></i>';
                var avatarEl = el.querySelector('.slot-avatar');
                avatarEl.textContent = getInitials(novoNome);
                avatarEl.style.background = s.avatarColor;
            } else {
                alert(resp.error || 'Erro ao salvar nome.');
            }
        });
    });

    // START
    initDashboard();
});
</script>
