/**
 * CRX Agentic Copilot & AI Drawer Client
 * Native JavaScript (zero dependencies)
 * Features: Session-persisted chat chain, 52-tool MCP execution, DOM screen intelligence
 */

// 1. Chat Chain Session Storage Key
const COPILOT_STORAGE_KEY = 'crx_copilot_history_v1';
let copilotHistory = [];

// Initialize history from sessionStorage on load
function initCopilotHistory() {
    try {
        const saved = sessionStorage.getItem(COPILOT_STORAGE_KEY);
        if (saved) {
            copilotHistory = JSON.parse(saved);
            const list = document.getElementById('aiMessages');
            if (list && copilotHistory.length > 0) {
                list.innerHTML = '';
                // Render restored chat chain
                copilotHistory.forEach(msg => {
                    const isUser = msg.role === 'user';
                    const content = isUser ? escapeHtml(msg.content) : formatAiReply(msg.content);
                    appendAiMessage(isUser ? 'user' : 'assistant', content, false);
                });
            }
        }
    } catch (e) {
        console.warn('Could not restore copilot history:', e);
        copilotHistory = [];
    }
}

function saveCopilotHistory() {
    try {
        if (copilotHistory.length > 20) {
            copilotHistory = copilotHistory.slice(-20);
        }
        sessionStorage.setItem(COPILOT_STORAGE_KEY, JSON.stringify(copilotHistory));
    } catch (e) {
        console.warn('Could not save copilot history:', e);
    }
}

function clearCopilotChat() {
    sessionStorage.removeItem(COPILOT_STORAGE_KEY);
    copilotHistory = [];
    const list = document.getElementById('aiMessages');
    if (list) {
        list.innerHTML = `
            <div class="ai-msg ai-msg-assistant">
                <strong>🤖 CRX Copilot:</strong>
                <p style="margin:4px 0 0 0;">Chat chain cleared. How can I assist you with your sales pipeline or execute CRM actions today?</p>
            </div>
        `;
    }
}

function toggleAiDrawer() {
    const drawer = document.getElementById('aiDrawer');
    const overlay = document.getElementById('aiDrawerOverlay');
    if (!drawer || !overlay) return;

    const isOpen = drawer.classList.contains('active');
    if (isOpen) {
        drawer.classList.remove('active');
        overlay.classList.remove('active');
    } else {
        drawer.classList.add('active');
        overlay.classList.add('active');
        const input = document.getElementById('aiUserInput');
        if (input) setTimeout(() => input.focus(), 150);
        
        // Ensure scrolled to bottom
        const list = document.getElementById('aiMessages');
        if (list) list.scrollTop = list.scrollHeight;
    }
}

function sendQuickPrompt(promptText) {
    const input = document.getElementById('aiUserInput');
    if (input) {
        input.value = promptText;
        const form = document.getElementById('aiChatForm');
        if (form) form.dispatchEvent(new Event('submit', { cancelable: true }));
    }
}

function captureScreenContext() {
    try {
        const ctx = {
            url: window.location.href,
            pathname: window.location.pathname,
            search: window.location.search,
            title: document.title || '',
            page_header: document.querySelector('.page-title')?.innerText?.trim() || '',
            page_subtitle: document.querySelector('.page-subtitle')?.innerText?.trim() || '',
            active_pipeline: document.querySelector('.btn-primary[href*="pipeline_id"]')?.innerText?.trim() || '',
            visible_metrics: [],
            table_summary: null,
            detail_summary: null
        };

        // Capture visible badges / metrics
        document.querySelectorAll('.stat-card, .kanban-column-header, .page-header [style*="background"]').forEach(el => {
            const txt = el.innerText?.trim();
            if (txt && txt.length < 80) ctx.visible_metrics.push(txt.replace(/\s+/g, ' '));
        });

        // Capture visible table data (first 12 rows)
        const table = document.querySelector('table.data-table, table');
        if (table) {
            const headers = Array.from(table.querySelectorAll('thead th'))
                .map(th => th.innerText.trim())
                .filter(t => t && t !== 'ACTIONS' && t !== '');
            const rows = [];
            table.querySelectorAll('tbody tr').forEach((tr, i) => {
                if (i >= 12) return;
                const cells = Array.from(tr.querySelectorAll('td'))
                    .map(td => td.innerText.trim())
                    .filter(t => t && t.length < 150);
                if (cells.length > 0) rows.push(cells.join(' | '));
            });
            if (rows.length > 0) {
                ctx.table_summary = {
                    columns: headers,
                    visible_rows: rows,
                    total_displayed: rows.length
                };
            }
        }

        // Capture detail layout if present
        const detailCard = document.querySelector('.detail-card, .detail-layout');
        if (detailCard) {
            ctx.detail_summary = detailCard.innerText.slice(0, 600).replace(/\s+/g, ' ');
        }

        return ctx;
    } catch (e) {
        return { url: window.location.href, pathname: window.location.pathname };
    }
}

async function handleAiSubmit(event) {
    if (event) event.preventDefault();
    const input = document.getElementById('aiUserInput');
    if (!input) return;

    const query = input.value.trim();
    if (!query) return;

    input.value = '';
    appendAiMessage('user', escapeHtml(query));

    const typingId = appendTypingIndicator();

    try {
        const screenContext = captureScreenContext();

        const response = await fetch('/api/copilot/chat', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                prompt: query,
                history: copilotHistory,
                screen_context: screenContext
            })
        });

        removeElement(typingId);

        if (response.ok) {
            const data = await response.json();
            const replyText = data.reply || 'No response received.';
            
            // Format markdown text (bold, bullets, newlines)
            const formattedReply = formatAiReply(replyText);
            appendAiMessage('assistant', formattedReply);

            // Record conversation history (Up to 20 turns for deep chat chain memory)
            copilotHistory.push({ role: 'user', content: query });
            copilotHistory.push({ role: 'assistant', content: replyText });
            saveCopilotHistory();

            // If an action proposal card was generated
            if (data.action_proposal) {
                appendActionProposalCard(data.action_proposal);
            }
        } else {
            appendAiMessage('assistant', '⚠️ Could not communicate with Copilot service.');
        }
    } catch (e) {
        removeElement(typingId);
        appendAiMessage('assistant', '⚠️ Connection Error: ' + e.message);
    }
}

function formatAiReply(text) {
    if (!text) return '';
    let out = escapeHtml(text);
    // Bold **text**
    out = out.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    // Bullet points
    out = out.replace(/^[•\-\*]\s+(.*)$/gm, '• $1<br>');
    // Newlines to <br>
    out = out.replace(/\n\n/g, '<br><br>').replace(/\n/g, '<br>');
    return out;
}

function appendAiMessage(role, htmlContent, scroll = true) {
    const list = document.getElementById('aiMessages');
    if (!list) return;

    const div = document.createElement('div');
    div.className = `ai-msg ai-msg-${role}`;
    div.innerHTML = htmlContent;
    list.appendChild(div);
    if (scroll) list.scrollTop = list.scrollHeight;
}

function appendActionProposalCard(proposal) {
    const list = document.getElementById('aiMessages');
    if (!list) return;

    const cardId = 'action_' + Date.now();
    const div = document.createElement('div');
    div.className = 'ai-msg ai-msg-assistant';

    const isMcp = proposal.type === 'mcp' || proposal.tool;
    const toolName = proposal.tool || '';
    const argsJson = JSON.stringify(proposal.arguments || proposal.payload || {});

    div.innerHTML = `
        <div class="ai-action-card" id="${cardId}">
            <div class="ai-action-card-header">⚡ MCP Action Proposal: <code>${escapeHtml(toolName || 'CRM Tool')}</code></div>
            <div class="ai-action-card-body">
                <strong>${escapeHtml(proposal.title)}</strong><br>
                <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">${proposal.description}</div>
            </div>
            <div class="ai-action-card-btns">
                <button class="btn btn-primary btn-sm" onclick="executeMcpAction('${cardId}', '${escapeHtml(toolName)}', '${encodeURIComponent(argsJson)}')">
                    ⚡ Approve & Execute
                </button>
                <button class="btn btn-secondary btn-sm" onclick="dismissProposal('${cardId}')">
                    Dismiss
                </button>
            </div>
        </div>
    `;
    list.appendChild(div);
    list.scrollTop = list.scrollHeight;
}

async function executeMcpAction(cardId, toolName, encodedArgs) {
    const card = document.getElementById(cardId);
    if (!card) return;

    const args = JSON.parse(decodeURIComponent(encodedArgs));
    card.innerHTML = `<span style="color:var(--primary);font-weight:700;">⏳ Executing MCP tool [${escapeHtml(toolName)}]...</span>`;

    try {
        const res = await fetch('/api/copilot/execute-mcp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                tool: toolName,
                arguments: args
            })
        });

        if (res.ok) {
            const data = await res.json();
            const result = data.data || {};

            let detailHtml = '';
            if (result.public_url) {
                detailHtml = `<div style="margin-top:6px;"><a href="${result.public_url}" target="_blank" class="btn btn-sm btn-primary" style="display:inline-block;padding:3px 8px;text-decoration:none;">📄 View Public Quote (${result.quote_token})</a></div>`;
            } else if (result.booking_url) {
                detailHtml = `<div style="margin-top:6px;"><a href="${result.booking_url}" target="_blank" class="btn btn-sm btn-primary" style="display:inline-block;padding:3px 8px;text-decoration:none;">📅 View Booking Page (${result.slug})</a></div>`;
            } else if (result.score !== undefined) {
                detailHtml = `<div style="margin-top:4px;font-size:12px;"><strong>Score:</strong> ${result.score}/100 (${result.tier}) | <strong>Recommendation:</strong> ${escapeHtml(result.recommended_action || 'N/A')}</div>`;
            } else if (result.id) {
                detailHtml = `<div style="margin-top:4px;font-size:12px;color:var(--text-muted);">Record ID: #${result.id} created successfully.</div>`;
            } else if (result.message) {
                detailHtml = `<div style="margin-top:4px;font-size:12px;color:var(--text-muted);">${escapeHtml(result.message)}</div>`;
            }

            card.innerHTML = `
                <div style="color:#16a34a;font-weight:700;margin-bottom:2px;">✅ MCP Tool '${escapeHtml(toolName)}' Executed!</div>
                ${detailHtml}
            `;

            // Append execution event to conversation history to respect the chat chain
            copilotHistory.push({
                role: 'assistant',
                content: `[Executed MCP Tool ${toolName} successfully. Details: ${JSON.stringify(result)}]`
            });
            saveCopilotHistory();

        } else {
            const err = await res.json();
            card.innerHTML = `<span style="color:#dc2626;font-weight:700;">❌ Execution failed: ${escapeHtml(err.error || 'Server error')}</span>`;
        }
    } catch (e) {
        card.innerHTML = `<span style="color:#dc2626;font-weight:700;">❌ Network error: ${escapeHtml(e.message)}</span>`;
    }
}

function dismissProposal(cardId) {
    const card = document.getElementById(cardId);
    if (card) {
        card.innerHTML = `<span style="color:var(--text-dim);font-style:italic;">Action proposal dismissed.</span>`;
    }
}

function appendTypingIndicator() {
    const list = document.getElementById('aiMessages');
    if (!list) return '';

    const id = 'typing_' + Date.now();
    const div = document.createElement('div');
    div.id = id;
    div.className = 'ai-msg ai-msg-assistant';
    div.innerHTML = `<span style="color:var(--text-dim);font-style:italic;">Agent is reasoning with MCP tools...</span>`;
    list.appendChild(div);
    list.scrollTop = list.scrollHeight;
    return id;
}

function removeElement(id) {
    if (!id) return;
    const el = document.getElementById(id);
    if (el) el.remove();
}

function escapeHtml(text) {
    if (typeof text !== 'string') return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Auto-initialize chat history on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCopilotHistory);
} else {
    initCopilotHistory();
}
