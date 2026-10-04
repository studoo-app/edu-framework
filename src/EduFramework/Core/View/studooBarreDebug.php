<?php

/*
 * Edu Framework by studoo
 *
 * @author Benoit Foujols
 *
 * Pour les informations complètes sur les droits d'auteur et la licence,
 * veuillez consulter le fichier LICENSE qui a été distribué avec ce code source.
 */

namespace Studoo\EduFramework\Core\View;

use Studoo\EduFramework\Core\ConfigCore;

class studooBarreDebug
{
    use studooView;

    /**
     * @return string Retourne CSS global
     */
    public function generateCssGlobal(): string
    {
        return "<style>
                        .barDebug {
                            font-family: 'Open Sans', sans-serif;
                            font-size: 16px;
                            display: flex; 
                            position: fixed; 
                            bottom: 0;
                            right: 0;
                            left: 0;
                            background-color: #000000;
                            padding: 5px;
                            text-align: center;
                            color: white;
                            z-index: 9999;
                        }
                        
                        #logoEF:hover #infoEF {
                            display: block;
                        }

                        #eduLogsToggle {
                            cursor: pointer;
                        }

                        #eduLogsPanel {
                            display: none;
                            position: fixed;
                            left: 0;
                            right: 0;
                            bottom: 30px;
                            max-height: 45vh;
                            overflow-y: auto;
                            background-color: #0d0d0d;
                            color: #e0e0e0;
                            border-top: 2px solid #36b393;
                            font-family: 'Open Sans', sans-serif;
                            font-size: 12px;
                            z-index: 9998;
                        }

                        .eduLogsHeader {
                            display: flex;
                            align-items: center;
                            gap: 8px;
                            padding: 6px 10px;
                            position: sticky;
                            top: 0;
                            background-color: #0d0d0d;
                            border-bottom: 1px solid #333;
                        }

                        .eduLogsTable {
                            width: 100%;
                            border-collapse: collapse;
                        }

                        .eduLogsTable th, .eduLogsTable td {
                            text-align: left;
                            padding: 4px 10px;
                            border-bottom: 1px solid #222;
                            white-space: nowrap;
                        }

                        .eduLogsTable td.eduRaw {
                            white-space: pre-wrap;
                            color: #9e9e9e;
                        }

                        .m-GET { color: #000; background-color: #61affe; padding: 1px 5px; border-radius: 3px; }
                        .m-POST { color: #000; background-color: #ffc862; padding: 1px 5px; border-radius: 3px; }
                        .m-PUT { color: #000; background-color: #fca02f; padding: 1px 5px; border-radius: 3px; }
                        .m-DELETE { color: #fff; background-color: #f93e3e; padding: 1px 5px; border-radius: 3px; }
                        .m-PATCH { color: #000; background-color: #50e3c2; padding: 1px 5px; border-radius: 3px; }

                        .s-2xx { color: #4caf50; font-weight: bold; }
                        .s-3xx { color: #80cbc4; font-weight: bold; }
                        .s-4xx { color: #ffb74d; font-weight: bold; }
                        .s-5xx { color: #ef5350; font-weight: bold; }

                        .eduBtn {
                            cursor: pointer;
                            background-color: #222;
                            color: #fff;
                            border: 1px solid #444;
                            padding: 2px 8px;
                            border-radius: 3px;
                            font-size: 12px;
                        }

                        .eduBtn[disabled] {
                            opacity: 0.4;
                            cursor: default;
                        }

                </style>";
    }

    /**
     * @return string Retourne la barre de débogage
     */
    public function generateBarDebug(): string
    {
        return '<div class="barDebug">
                    <div style="flex: 4;">
                        <div style="display: flex; justify-content: flex-start; align-items: center;">
                            <span style="color: black; padding-right: 3px; padding-left: 3px; background: palegoldenrod; padding-left: 1px">' . ConfigCore::getRequest()->getHttpMethod() . '</span>
                            <span style="color: black; padding-right: 3px; padding-left: 3px; background: palegoldenrod; padding-left: 1px">' . ConfigCore::getRequest()->getRoute() . '</span>
                            <span id="eduLogsToggle" style="color: black; margin-left: 10px; padding-right: 3px; padding-left: 3px; background: palegoldenrod;">Logs</span>
                            <span style="color: white; margin-left: 10px"><a href="https://studoo-app.github.io/edu-framework" target="_blank" style="color: white;">Documentation</a></span>
                         </div>
                    </div>
                    
                    <div style="flex: 8;"></div>
                    
                    <div style="flex: 4;">
                        <div style="display: flex; justify-content: flex-end; align-items: center;">
                            <img src="' . $this->logo() . '" width="20px" id="logoEF">     
                            <span style="padding-left: 5px;">' . ConfigCore::getConfig('version') . '</span>
                        </div>
                         <div id="infoEF" style="display: none; position: absolute; bottom: 30px; right: 0; background-color: #000; color: #fff; padding: 10px;">
                           Test
                         </div>
                   </div>
                 </div>
                 ' . $this->generatePanelLogs() . '
                 ' . $this->generateJsBarDebug() . '
                ';
    }

    /**
     * @return string Retourne le panneau de navigation des logs
     */
    private function generatePanelLogs(): string
    {
        return '<div id="eduLogsPanel">
                    <div class="eduLogsHeader">
                        <strong>Logs</strong>
                        <span id="eduLogsCount"></span>
                        <span style="flex: 1;"></span>
                        <button type="button" id="eduLogsPrev" class="eduBtn">&#9664;</button>
                        <span id="eduLogsPageInfo">1 / 1</span>
                        <button type="button" id="eduLogsNext" class="eduBtn">&#9654;</button>
                        <button type="button" id="eduLogsRefresh" class="eduBtn">&#10227;</button>
                    </div>
                    <table class="eduLogsTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Méthode</th>
                                <th>Statut</th>
                                <th>IP:port</th>
                                <th>Chemin</th>
                                <th>Reçue le</th>
                            </tr>
                        </thead>
                        <tbody id="eduLogsBody"></tbody>
                    </table>
                </div>';
    }

    /**
     * @return string Retourne le JavaScript de la barre de débogage
     */
    private function generateJsBarDebug(): string
    {
        return '<script>
                (function() {
                    var toggle = document.getElementById("eduLogsToggle");
                    var panel = document.getElementById("eduLogsPanel");
                    if (toggle === null || panel === null) {
                        return;
                    }

                    var state = { page: 1, limit: 20, pages: 1, open: false };

                    function createCell(content) {
                        var td = document.createElement("td");
                        td.textContent = content;
                        return td;
                    }

                    function createBadge(value, prefix) {
                        var span = document.createElement("span");
                        span.className = prefix + value;
                        span.textContent = value;
                        return span;
                    }

                    function createStatusBadge(statusCode) {
                        var span = document.createElement("span");
                        span.className = "s-" + String(statusCode).charAt(0) + "xx";
                        span.textContent = statusCode;
                        return span;
                    }

                    function renderLogs(data) {
                        var body = document.getElementById("eduLogsBody");
                        body.innerHTML = "";

                        document.getElementById("eduLogsCount").textContent = "(" + data.total + ")";

                        (data.logs || []).forEach(function(log) {
                            var event = log.event || {};
                            var tr = document.createElement("tr");

                            tr.appendChild(createCell("#" + log.id));
                            tr.appendChild(createCell(log.event_date));

                            if (event.method !== undefined && event.method !== null) {
                                var tdMethod = document.createElement("td");
                                tdMethod.appendChild(createBadge(event.method, "m-"));
                                tr.appendChild(tdMethod);

                                var tdStatus = document.createElement("td");
                                tdStatus.appendChild(createStatusBadge(event.status_code));
                                tr.appendChild(tdStatus);

                                tr.appendChild(createCell(event.ip_port));
                                tr.appendChild(createCell(event.path));
                                tr.appendChild(createCell(event.timestamp));
                            } else {
                                var tdRaw = document.createElement("td");
                                tdRaw.colSpan = 4;
                                tdRaw.className = "eduRaw";
                                tdRaw.textContent = event.raw;
                                tr.appendChild(tdRaw);
                            }

                            body.appendChild(tr);
                        });

                        state.pages = data.pages || 1;
                        document.getElementById("eduLogsPageInfo").textContent = state.page + " / " + state.pages;
                        document.getElementById("eduLogsPrev").disabled = state.page <= 1;
                        document.getElementById("eduLogsNext").disabled = state.page >= state.pages;
                    }

                    function loadLogs() {
                        fetch("/edu-logs?page=" + state.page + "&limit=" + state.limit)
                            .then(function(response) { return response.json(); })
                            .then(renderLogs)
                            .catch(function(error) { console.error("[EduFramework Logs]", error); });
                    }

                    toggle.addEventListener("click", function() {
                        state.open = !state.open;
                        if (state.open === true) {
                            panel.style.display = "block";
                            panel.style.bottom = document.querySelector(".barDebug").offsetHeight + "px";
                            state.page = 1;
                            loadLogs();
                        } else {
                            panel.style.display = "none";
                        }
                    });

                    document.getElementById("eduLogsRefresh").addEventListener("click", loadLogs);

                    document.getElementById("eduLogsPrev").addEventListener("click", function() {
                        if (state.page > 1) {
                            state.page--;
                            loadLogs();
                        }
                    });

                    document.getElementById("eduLogsNext").addEventListener("click", function() {
                        if (state.page < state.pages) {
                            state.page++;
                            loadLogs();
                        }
                    });
                })();
                </script>';
    }
}
