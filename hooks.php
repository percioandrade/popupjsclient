<?php

use WHMCS\Module\Addon\PopupJSClient\ModuleController as AnnouncementController;
use WHMCS\Module\Addon\PopupJSClient\Model as Announcement;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

add_hook('ClientAreaFooterOutput', 1, 'ClientAreaFooterOutput_ModuleView');

/**
 * @throws Exception
 */
function ClientAreaFooterOutput_ModuleView($vars)
{
    // Check if the user is logged in
    if (!isset($vars['loggedin']) || !$vars['loggedin']) {
        return; // If you are not logged in, the popup does not display
    }

    $lang = AnnouncementController::getLang($vars);
    $userGroup = $vars['clientsdetails']['groupid'] ?? null;
    $announcement = Announcement::getLatest($userGroup);

    if ($announcement && !empty($announcement->announcement)) {
        Announcement::incrementViews($announcement->id);
        $announcementText = $announcement->announcement; // We don't escape here, we will do it in JavaScript
        $interactionLink = htmlspecialchars($lang['interaction_link'], ENT_QUOTES, 'UTF-8');
        $announcementId = (int)$announcement->id;
        $isMultimedia = $announcement->is_multimedia ? 'true' : 'false';
        $url = htmlspecialchars($announcement->url, ENT_QUOTES, 'UTF-8');

        echo "
        <style>
            .areaModal {
                -webkit-box-align: center;
                align-items: center;
                background-color: rgba(74, 74, 74, 0.8);
                display: flex;
                height: 100vh;
                min-height: 40vh;
                -webkit-box-pack: center;
                justify-content: center;
                left: 0px;
                position: fixed;
                top: 0px;
                width: 100vw;
                z-index: 999;
            }
    
            .areaModal {
                position: fixed;
                top: -100%; /* Inicialmente fora da tela, acima */
                left: 0;
                width: 100%;
                height: 100vh;
                background-color: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
                transition: top 0.5s ease; /* Transição suave ao deslizar */
            }

            .areaModal.show {
                top: 0; /* Move para a posição original */
            }
                
            .areaModalButton {
                position: absolute;
                right: 0;
                margin: 10px;
                top: -5px;
                background: #e2f4fb;
                padding: 1px 15px;
                font-size: 14px;
                color: #235498;
                font-weight: bold;
                font-family: 'Plus Jakarta Sans';
            }
        </style>
        
        <script>
            (function() {
                var announcementData = {
                    id: $announcementId,
                    isMultimedia: $isMultimedia,
                    url: '$url',
                    text: " . json_encode($announcementText) . ",
                    interactionLink: '$interactionLink'
                };

                window.addEventListener('load', function() {
                    var cookieName = 'modalDimissID_' + announcementData.id;
        
                    // Checks if the cookie already exists
                    if (getCookie(cookieName)) {
                        return; // Does not display if cookie is already set
                    }
        
                    // Creating the popup
                    var popup = document.createElement('div');
                    popup.innerHTML = 
                        '<div id=\"areaModal\" class=\"areaModal\">' +
                            '<div id=\"areaModalVal\" class=\"areaModalVal\">' +
                                (announcementData.isMultimedia 
                                    ? '<a href=\"' + announcementData.url + '\" target=\"_blank\"><img src=\"' + announcementData.text + '\" alt=\"Announcement\" /></a>' 
                                    : '<div>' + parseAndSanitizeHTML(announcementData.text) + '</div>') +
                                '<a href=\"#\" class=\"areaModalButton\" onclick=\"dismissAnnouncement(' + announcementData.id + '); return false;\">' + announcementData.interactionLink + '</a>' +
                            '</div>' +
                        '</div>';
                    document.body.appendChild(popup);
                });
        
                function dismissAnnouncement(announcementId) {
                    var cookieName = 'modalDimissID_' + announcementId;
                    setCookie(cookieName, '1', 1); // Sets the cookie for 1 day (24 hours)
                    document.getElementById('areaModal').remove();
                }
        
                function setCookie(name, value, days) {
                    var date = new Date();
                    date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
                    document.cookie = name + '=' + value + '; path=/; expires=' + date.toUTCString();
                }
        
                function getCookie(name) {
                    var cookies = document.cookie.split(';');
                    for (var i = 0; i < cookies.length; i++) {
                        var cookie = cookies[i].trim();
                        if (cookie.startsWith(name + '=')) {
                            return cookie.substring(name.length + 1);
                        }
                    }
                    return null;
                }

                function parseAndSanitizeHTML(html) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(html, 'text/html');
                    return doc.body.innerHTML;
                }

                // Make dismissAnnouncement globally accessible
                window.dismissAnnouncement = dismissAnnouncement;
            })();
        </script>
        ";
    }
}