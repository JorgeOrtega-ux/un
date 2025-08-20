<div class="section-content disabled" data-section="sectionChat">
    <div class="chat-section-container" id="chat-section-container">

        <div class="chat-content-area">
            <div class="chat-messages-wrapper" id="chat-messages-wrapper">
                <div class="chat-messages-container" id="chat-messages-container">
                    </div>
            </div>
            <div class="chat-input-container">
                <div class="reply-preview-container disabled" id="reply-preview-container">
                    <div class="reply-preview-content">
                        <strong class="reply-preview-author"></strong>
                        <p class="reply-preview-text"></p>
                    </div>
                    <button class="reply-preview-close" data-action="cancel-reply">
                        <span class="material-symbols-rounded">close</span>
                    </button>
                </div>
                <form class="chat-input-group" id="chat-form">
                    <button type="button" class="chat-attach-button">
                        <span class="material-symbols-rounded">attachment</span>
                    </button>
                    <input type="text" class="chat-input-field" placeholder="Escribe un mensaje..." autocomplete="off" maxlength="500">
                    <button type="submit" class="chat-send-button">
                        <span class="material-symbols-rounded">send</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="chat-members-sidebar">
            <div class="sidebar-header">
                <h3 id="sidebar-group-title"></h3>
                <span id="sidebar-online-count"></span>
            </div>
            <div class="sidebar-content" id="chat-members-list">
                </div>
        </div>

        <div class="module-content module-options body-title disabled" id="message-options-template">
            <div class="menu-content">
                <div class="menu-body">
                    <div class="menu-list">
                        <div class="menu-link" data-action="reply-message">
                            <div class="menu-link-icon"><span class="material-symbols-rounded">reply</span></div>
                            <div class="menu-link-text"><span>Responder</span></div>
                        </div>
                        <div class="menu-link" data-action="copy-message">
                            <div class="menu-link-icon"><span class="material-symbols-rounded">content_copy</span></div>
                            <div class="menu-link-text"><span>Copiar</span></div>
                        </div>
                        <div class="menu-link" data-action="delete-message" style="display: none; color: #d93025;">
                            <div class="menu-link-icon"><span class="material-symbols-rounded">delete</span></div>
                            <div class="menu-link-text"><span>Eliminar</span></div>
                        </div>
                        <div class="menu-link" data-action="report-message">
                            <div class="menu-link-icon"><span class="material-symbols-rounded">report</span></div>
                            <div class="menu-link-text"><span>Reportar</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
</div>