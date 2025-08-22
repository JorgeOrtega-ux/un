<div class="section-content disabled" data-section="sectionChat">


    <div class="section-content">



        <div class="content-container">
            <div class="chat-section-container" id="chat-section-container">

                <div class="chat-content-area">
                    <div class="chat-messages-wrapper" id="chat-messages-wrapper">
                        <div class="chat-loader-container" id="chat-loader-container" style="display: none;">
                            <div class="loader"></div>
                        </div>
                        <div class="chat-welcome-message" id="chat-welcome-message" style="display: none;">
                            <span class="material-symbols-rounded chat-welcome-icon">waving_hand</span>
                            <h2>¡Empieza la conversación!</h2>
                            <p>Aún no hay mensajes en este chat. ¡Sé el primero en enviar uno!</p>
                        </div>
                        <div class="chat-messages-container" id="chat-messages-container">
                        </div>
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
    </div>
    <div class="section-content chat">
        <div class="chat-input-container">
            <div class="mention-container disabled" id="mention-container">
                <div class="mention-list" id="mention-list"></div>
            </div>
            <div class="reply-preview-container disabled" id="reply-preview-container">
                <img src="" class="reply-preview-image" alt="Vista previa de respuesta">
                <div class="reply-preview-content">
                    <strong class="reply-preview-author"></strong>
                    <p class="reply-preview-text"></p>
                </div>
                <button class="reply-preview-close" data-action="cancel-reply">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            <div class="image-preview-container disabled" id="image-preview-container">
                <img src="" alt="Vista previa de la imagen" id="image-preview">
                <button class="image-preview-close" data-action="cancel-image-preview">
                    <span class="material-symbols-rounded">close</span>
                </button>
            </div>
            <form class="chat-input-group" id="chat-form">
                <div class="chat-input-actions">
                    <button type="button" class="chat-attach-button" data-action="toggle-attach-dropdown">
                        <span class="material-symbols-rounded">attachment</span>
                    </button>
                </div>
                <input type="text" class="chat-input-field" placeholder="Escribe un mensaje..." autocomplete="off" maxlength="500">
                <button type="submit" class="chat-send-button">
                    <span class="material-symbols-rounded">send</span>
                </button>
                <input type="file" id="image-input" accept="image/*" style="display: none;">
            </form>
            <div class="module-content module-options body-title disabled" id="attach-dropdown-template">
                <div class="menu-content">
                    <div class="menu-body">
                        <div class="menu-list">
                            <div class="menu-link" data-action="attach-photo">
                                <div class="menu-link-icon"><span class="material-symbols-rounded">image</span></div>
                                <div class="menu-link-text"><span>Adjuntar foto</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>







</div>

<style>
    .chat {
        height: auto;
    }
</style>