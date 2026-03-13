// HealthMate Chatbot Widget
(function() {
    // Allow conditional enable/disable at runtime
    var chatbotInitialized = false;
    var elements = { toggleBtn: null, panel: null };
    if (window.__HM_CHATBOT_WIRED__) return;
    window.__HM_CHATBOT_WIRED__ = true;

    function createEl(tag, attrs, children) {
        var el = document.createElement(tag);
        if (attrs) {
            for (var k in attrs) {
                if (!Object.prototype.hasOwnProperty.call(attrs, k)) continue;
                if (k === 'class') el.className = attrs[k];
                else if (k === 'text') el.textContent = attrs[k];
                else el.setAttribute(k, attrs[k]);
            }
        }
        (children || []).forEach(function(c){ el.appendChild(c); });
        return el;
    }

    function initChatbot() {
        if (chatbotInitialized) return;
        var toggleBtn = createEl('button', { class: 'hm-chatbot-toggle', title: 'Chat with HealthMate', type: 'button', 'aria-label': 'Open chatbot' });
        toggleBtn.innerHTML = '<i class="fas fa-comment"></i>';
        // Ensure visibility even if CSS fails to load
        try {
            toggleBtn.style.position = 'fixed';
            toggleBtn.style.bottom = '20px';
            toggleBtn.style.right = '20px';
            toggleBtn.style.zIndex = '2147483000';
            if (!toggleBtn.style.width) toggleBtn.style.width = '56px';
            if (!toggleBtn.style.height) toggleBtn.style.height = '56px';
            toggleBtn.style.borderRadius = '50%';
        } catch (e) {}

        var panel = createEl('div', { class: 'hm-chatbot-panel', role: 'dialog', 'aria-label': 'HealthMate Assistant' });
        var header = createEl('div', { class: 'hm-chatbot-header', text: 'HealthMate Assistant' });
        var prompts = createEl('div', { class: 'hm-chatbot-prompts', style: 'display:none;' });
        var messages = createEl('div', { class: 'hm-chatbot-messages' });
        var inputRow = createEl('div', { class: 'hm-chatbot-input' });
        var input = createEl('input', { type: 'text', placeholder: 'Ask me anything…', class: 'form-control' });
        var sendBtn = createEl('button', { class: 'btn btn-primary', type: 'button' });
        sendBtn.textContent = 'Send';

        inputRow.appendChild(input);
        inputRow.appendChild(sendBtn);
        panel.appendChild(header);
        panel.appendChild(prompts);
        panel.appendChild(messages);
        panel.appendChild(inputRow);
        function addPromptButtons() {
            var base = (location.pathname.indexOf('/pages/') !== -1) ? '' : 'pages/';
            var items = [
                { label: 'My Stats', url: base + 'dashboard.php#stats' },
                { label: 'Recent Activity', url: base + 'dashboard.php#recent_activity' },
                { label: 'Nutrition Tracker', url: base + 'dashboard.php#trackerContent' },
                { label: 'Leaderboard', url: base + 'leaderboard.php' },
                { label: 'Workouts', url: base + 'workouts.php' },
                { label: 'Profile', url: base + 'profile.php' },
                { label: 'Achievements', url: base + 'achievements.php' },
                { label: 'BMR Calculator', url: base + 'dashboard.php#bmr' }
            ];
            // Remove options not present: if targeting a section anchor on the current page, ensure it exists
            try {
                var currentPath = location.pathname.split('/').pop();
                items = items.filter(function(it){
                    // Always show BMR Calculator option
                    if (it.label === 'BMR Calculator') return true;
                    var hashIndex = it.url.indexOf('#');
                    if (hashIndex === -1) return true;
                    var targetPage = it.url.substring(0, hashIndex).split('/').pop();
                    var anchor = it.url.substring(hashIndex + 1);
                    // Only validate anchors when the target page is the current page
                    if (targetPage && currentPath && targetPage.toLowerCase() === currentPath.toLowerCase()) {
                        return !!document.getElementById(anchor);
                    }
                    return true;
                });
            } catch (e) {}
            var botRow = createEl('div', { class: 'hm-chat-msg bot' });
            var label = createEl('div', { text: 'Quick actions you can take now:' });
            botRow.appendChild(label);
            var group = createEl('div', { style: 'margin-top:6px; display:flex; flex-wrap:wrap; gap:8px;' });
            items.forEach(function(it){
                var b = createEl('button', { class: 'btn btn-outline-secondary btn-sm', type: 'button', title: 'Go to ' + it.label });
                b.textContent = '➜ ' + it.label;
                b.addEventListener('click', function(){
                    addMsg(it.label, 'user');
                    addMsg('Opening ' + it.label + '…', 'bot');
                    setTimeout(function(){
                        try { if (elements && elements.panel) { elements.panel.style.display = 'none'; } } catch (e) {}
                        try {
                            var hashIndex = it.url.indexOf('#');
                            var targetPath = hashIndex === -1 ? it.url : it.url.substring(0, hashIndex);
                            var targetHash = hashIndex === -1 ? '' : it.url.substring(hashIndex);
                            var currentPath = location.pathname.split('/').pop();
                            var targetFile = targetPath ? targetPath.split('/').pop() : currentPath;
                            function ensureVisibleForHash(h) {
                                try {
                                    if (h === '#bmr') {
                                        var bmrPanel = document.getElementById('bmrContent');
                                        if (bmrPanel) {
                                            bmrPanel.style.display = 'block';
                                            bmrPanel.classList.add('active','show');
                                        }
                                    } else if (h === '#trackerContent') {
                                        var trackerPanel = document.getElementById('trackerContent');
                                        if (trackerPanel) {
                                            trackerPanel.style.display = 'block';
                                            trackerPanel.classList.add('active','show');
                                        }
                                    }
                                } catch (e) {}
                            }
                            if (targetFile && currentPath && targetFile.toLowerCase() === currentPath.toLowerCase() && targetHash) {
                                // Same page: ensure section visible (tabs), then update hash for smooth jump
                                ensureVisibleForHash(targetHash);
                                var el = document.getElementById(targetHash.replace('#',''));
                                if (el && typeof el.scrollIntoView === 'function') {
                                    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                }
                                location.hash = targetHash;
                            } else {
                                // Different page or no hash: resolve relative to baseURI for robust navigation
                                var resolved = new URL(it.url, document.baseURI).toString();
                                window.location.assign(resolved);
                            }
                        } catch (e) {
                            // Fallback
                            window.location.href = it.url;
                        }
                    }, 700);
                });
                group.appendChild(b);
            });
            botRow.appendChild(group);
            messages.appendChild(botRow);
            messages.scrollTop = messages.scrollHeight;
        }

        // Show prompt options inside the conversation area
        setTimeout(addPromptButtons, 300);

        document.body.appendChild(toggleBtn);
        document.body.appendChild(panel);

        elements.toggleBtn = toggleBtn;
        elements.panel = panel;

        function showPanel(show) {
            panel.style.display = show ? 'flex' : 'none';
        }

        toggleBtn.addEventListener('click', function() {
            var isHidden = panel.style.display === 'none' || panel.style.display === '';
            showPanel(isHidden);
            if (isHidden) {
                input.focus();
            }
        });

        function addMsg(text, who) {
            var bubble = createEl('div', { class: 'hm-chat-msg ' + (who === 'user' ? 'user' : 'bot') });
            bubble.textContent = text;
            messages.appendChild(bubble);
            messages.scrollTop = messages.scrollHeight;
        }

        function addOptions(options) {
            var row = createEl('div', { class: 'hm-chat-msg bot' });
            options.forEach(function(opt){
                var btn = createEl('button', { class: 'btn btn-outline-primary btn-sm', type: 'button' });
                btn.textContent = opt.label;
                btn.addEventListener('click', function(){
                    addMsg(opt.label, 'user');
                    if (typeof opt.onSelect === 'function') {
                        opt.onSelect();
                    } else if (opt.message) {
                        handleText(opt.message);
                    }
                });
                row.appendChild(btn);
                row.appendChild(document.createTextNode(' '));
            });
            messages.appendChild(row);
            messages.scrollTop = messages.scrollHeight;
        }

        var state = { flow: null };

        function handleLocalRules(text) {
            var t = (text || '').toLowerCase().trim();

            // Example IF/ELSE style conditional flows
            if (t === 'diet' || t === 'meal plan') {
                state.flow = 'diet_goal';
                addMsg('What is your goal?', 'bot');
                addOptions([
                    { label: 'Lose Weight', message: 'goal: lose weight' },
                    { label: 'Build Muscle', message: 'goal: build muscle' },
                    { label: 'Maintain', message: 'goal: maintain' }
                ]);
                return true;
            }

            if (state.flow === 'diet_goal' && t.indexOf('goal:') === 0) {
                state.flow = null;
                if (t.indexOf('lose weight') !== -1) {
                    addMsg('Focus on a slight calorie deficit and high-protein meals. Try: grilled chicken, leafy greens, berries.', 'bot');
                } else if (t.indexOf('build muscle') !== -1) {
                    addMsg('Aim for a small calorie surplus with 1.6–2.2g protein/kg bodyweight. Try: eggs, Greek yogurt, salmon.', 'bot');
                } else {
                    addMsg('Maintain by balancing calories to your TDEE. Mix lean proteins, whole grains, and vegetables.', 'bot');
                }
                return true;
            }

            if (t === 'bmi') {
                state.flow = 'bmi_height';
                addMsg('Enter your height in cm (e.g., height: 170)', 'bot');
                return true;
            }
            if (state.flow === 'bmi_height' && t.indexOf('height:') === 0) {
                var h = parseFloat(t.replace('height:', '').trim());
                if (!isFinite(h) || h <= 0) { addMsg('Please provide a valid height in cm.', 'bot'); return true; }
                state.height = h; state.flow = 'bmi_weight';
                addMsg('Now enter your weight in kg (e.g., weight: 65)', 'bot');
                return true;
            }
            if (state.flow === 'bmi_weight' && t.indexOf('weight:') === 0) {
                var w = parseFloat(t.replace('weight:', '').trim());
                if (!isFinite(w) || w <= 0) { addMsg('Please provide a valid weight in kg.', 'bot'); return true; }
                var m = (state.height || 0) / 100;
                var bmi = m > 0 ? (w / (m * m)) : 0;
                state.flow = null; state.height = undefined;
                addMsg('Your BMI is ' + bmi.toFixed(1) + '. (18.5–24.9 is considered normal range)', 'bot');
                return true;
            }

            // If no local rule matched
            return false;
        }

        function handleText(text) {
            if (handleLocalRules(text)) return; // handled by IF/ELSE rules
            // otherwise fallback to backend
            fetch(endpointPath(), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: text })
            })
            .then(function(r){ return r.json(); })
            .then(function(res){
                if (res && res.success && res.reply) {
                    addMsg(res.reply, 'bot');
                } else if (res && res.message) {
                    addMsg(res.message, 'bot');
                } else {
                    addMsg('Sorry, something went wrong. Please try again.', 'bot');
                }
            })
            .catch(function(){ addMsg('Network error. Please check your connection.', 'bot'); });
        }

        function endpointPath() {
            try {
                // If we are under /pages/, use ../php/, else use php/
                var isInPages = (location.pathname.indexOf('/pages/') !== -1);
                return isInPages ? '../php/chatbot_api.php' : 'php/chatbot_api.php';
            } catch (e) {
                return 'php/chatbot_api.php';
            }
        }

        function sendMessage() {
            var text = (input.value || '').trim();
            if (!text) return;
            addMsg(text, 'user');
            input.value = '';
            handleText(text);
        }

        sendBtn.addEventListener('click', sendMessage);
        input.addEventListener('keydown', function(e){
            if (e.key === 'Enter') sendMessage();
        });

        setTimeout(function(){
            addMsg('Hi! I\'m your HealthMate assistant. I can show your stats, recent activity, leaderboards, and more. You can also ask things like "calories in banana". Choose an option below to jump right in:', 'bot');
        }, 400);

        showPanel(false);
        chatbotInitialized = true;
    }

    // Public controller to enable/disable
    window.HM_CHATBOT_SET_ENABLED = function(enabled) {
        if (enabled) {
            if (!chatbotInitialized) {
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initChatbot);
                } else {
                    initChatbot();
                }
            } else if (elements.toggleBtn) {
                elements.toggleBtn.style.display = 'block';
            }
        } else {
            if (elements.panel) elements.panel.style.display = 'none';
            if (elements.toggleBtn) elements.toggleBtn.style.display = 'none';
        }
    };

    // Initialize based on initial flag
    var initiallyEnabled = true;
    try {
        if (typeof window !== 'undefined' && typeof window.HM_CHATBOT_ENABLED !== 'undefined') {
            initiallyEnabled = !!window.HM_CHATBOT_ENABLED;
        }
    } catch (e) {}

    window.HM_CHATBOT_SET_ENABLED(initiallyEnabled);
})();


