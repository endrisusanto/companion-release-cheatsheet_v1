<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Build Command Converter</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Variables for Theming */
        :root {
            --bg-primary: #0a0a0a;
            --text-primary: #e5e7eb;
            --text-secondary: #9ca3af;
            --glass-bg: rgba(20, 20, 30, 0.4);
            --glass-border: rgba(255, 255, 255, 0.08);
            --input-bg: rgba(17, 24, 39, 0.5); /* bg-gray-900/50 */
            --input-border: #374151; /* border-gray-700 */
            --summary-code-bg: rgba(255, 255, 255, 0.1);
            --summary-code-text: #c4b5fd;
        }

        html.light {
            --bg-primary: #f3f4f6;
            --text-primary: #111827;
            --text-secondary: #4b5563;
            --glass-bg: rgba(255, 255, 255, 0.6);
            --glass-border: rgba(0, 0, 0, 0.1);
            --input-bg: rgba(229, 231, 235, 0.7); /* bg-gray-200/70 */
            --input-border: #d1d5db; /* border-gray-300 */
            --summary-code-bg: rgba(0, 0, 0, 0.1);
            --summary-code-text: #4c1d95;
        }

        /* Custom styles */
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            transition: background-color 0.3s, color 0.3s;
            overflow: hidden; /* Prevent scrollbars from animation */
        }
        
        #neural-network-canvas {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        /* Glassmorphism effect */
        .glassmorphism {
            background: var(--glass-bg);
            border-radius: 16px;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            transition: background 0.3s, border 0.3s;
        }
        
        /* Themed text and inputs */
        .themed-text-secondary { color: var(--text-secondary); }
        .themed-input {
            background-color: var(--input-bg);
            border-color: var(--input-border);
        }

        /* "Magic" animation on text change */
        @keyframes magic-glow {
            0% { box-shadow: 0 0 0px #3b82f6; }
            50% { box-shadow: 0 0 15px #3b82f6; }
            100% { box-shadow: 0 0 0px #3b82f6; }
        }

        .animate-magic-glow {
            animation: magic-glow 0.6s ease-in-out;
        }
        
        /* Highlight styles */
        .highlight-mc { background-color: rgba(96, 165, 250, 0.2); color: #93c5fd; padding: 2px 6px; border-radius: 4px; font-weight: 700; } /* blue */
        .highlight-region { background-color: rgba(74, 222, 128, 0.2); color: #86efac; padding: 2px 6px; border-radius: 4px; font-weight: 700; } /* green */
        .highlight-teng { background-color: rgba(192, 132, 252, 0.2); color: #d8b4fe; padding: 2px 6px; border-radius: 4px; font-weight: 700; } /* purple */
        .highlight-mid { background-color: rgba(250, 204, 21, 0.2); color: #fde047; padding: 2px 6px; border-radius: 4px; font-weight: 700; } /* yellow */
        
        html.light .highlight-mc { color: #2563eb; }
        html.light .highlight-region { color: #16a34a; }
        html.light .highlight-teng { color: #7e22ce; }
        html.light .highlight-mid { color: #ca8a04; }

        /* Style for code snippets in summary */
        .summary-code {
            background-color: var(--summary-code-bg);
            color: var(--summary-code-text);
            padding: 2px 5px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9em;
        }

        /* Colored text for summary code, matching highlights */
        .summary-code.color-mc { color: #60a5fa; } /* Brighter Blue for Dark Mode */
        .summary-code.color-region { color: #4ade80; } /* Brighter Green for Dark Mode */
        .summary-code.color-teng { color: #c084fc; } /* Brighter Purple for Dark Mode */
        .summary-code.color-mid { color: #facc15; } /* Brighter Yellow for Dark Mode */
        
        html.light .summary-code.color-mc { color: #2563eb; }
        html.light .summary-code.color-region { color: #16a34a; }
        html.light .summary-code.color-teng { color: #7e22ce; }
        html.light .summary-code.color-mid { color: #ca8a04; }

        /* Custom scrollbar */
        textarea::-webkit-scrollbar, pre::-webkit-scrollbar { width: 8px; }
        textarea::-webkit-scrollbar-track, pre::-webkit-scrollbar-track { background: rgba(128, 128, 128, 0.1); border-radius: 10px; }
        textarea::-webkit-scrollbar-thumb, pre::-webkit-scrollbar-thumb { background: rgba(128, 128, 128, 0.3); border-radius: 10px; }
        textarea::-webkit-scrollbar-thumb:hover, pre::-webkit-scrollbar-thumb:hover { background: rgba(128, 128, 128, 0.5); }
        
        /* Toast notification that follows cursor */
        #toast-notification {
            position: fixed;
            opacity: 0;
            transform: translate(-50%, -120%);
            transition: opacity 0.3s ease-out, transform 0.3s ease-out;
            pointer-events: none; /* Prevents toast from blocking mouse events */
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <canvas id="neural-network-canvas"></canvas>

    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-7xl p-8 space-y-8 glassmorphism relative">
            
            <div class="absolute top-4 right-4">
                <button id="theme-toggle" class="p-2 rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-800 focus:ring-white">
                    <svg id="theme-toggle-dark-icon" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path></svg>
                    <svg id="theme-toggle-light-icon" class="w-6 h-6 hidden" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.707.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zM1 11a1 1 0 100-2H0a1 1 0 100 2h1zM4.59 15.41a1 1 0 010-1.414l.707-.707a1 1 0 011.414 1.414l-.707.707a1 1 0 01-1.414 0z"></path></svg>
                </button>
            </div>

            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold bg-gradient-to-r from-sky-400 to-cyan-300 bg-clip-text text-transparent tracking-tight">System Build Command Converter</h1>
                <p class="mt-2 text-lg themed-text-secondary">Konversi perintah build Anda secara otomatis dengan sentuhan ajaib.</p>
            </div>

            <div class="space-y-2">
                <label for="base-cmd" class="text-base font-semibold">BASE SYSTEM_BUILD_CMD</label>
                <textarea id="base-cmd" rows="4" class="w-full p-3 themed-input border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-300 text-base" placeholder="./build -oa -tuser -ma -i A336EDXUEGYH7 -j EGYH7 -B -Wt --sssi-build system -d low -s -r31998945 a33x_sea_open OLM OJM OLM_SUP OJM_SUP"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div id="user-output-container" class="space-y-2 transition-all duration-300">
                    <label class="text-base font-semibold">INDONESIA SYSTEM_BUILD_CMD USER</label>
                    <pre id="user-output" onclick="copyToClipboard(this, event)" class="w-full p-3 min-h-[9rem] overflow-auto themed-input border rounded-lg text-base whitespace-pre-wrap cursor-pointer transition-all"></pre>
                    <div id="user-changes-summary" class="mt-2 text-sm min-h-[2rem]"></div>
                </div>

                <div id="eng-output-container" class="space-y-2 transition-all duration-300">
                    <label class="text-base font-semibold">INDONESIA SYSTEM_BUILD_CMD ENG</label>
                    <pre id="eng-output" onclick="copyToClipboard(this, event)" class="w-full p-3 min-h-[9rem] overflow-auto themed-input border rounded-lg text-base whitespace-pre-wrap cursor-pointer transition-all"></pre>
                    <div id="eng-changes-summary" class="mt-2 text-sm min-h-[2rem]"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div id="toast-notification" class="bg-green-500 text-white py-2 px-4 rounded-lg shadow-lg">
        Teks berhasil disalin!
    </div>


    <script>
        // --- CORE APP LOGIC ---
        const baseCmdInput = document.getElementById('base-cmd');
        const userOutputEl = document.getElementById('user-output');
        const engOutputEl = document.getElementById('eng-output');
        const userOutputContainer = document.getElementById('user-output-container');
        const engOutputContainer = document.getElementById('eng-output-container');
        const userChangesSummaryEl = document.getElementById('user-changes-summary');
        const engChangesSummaryEl = document.getElementById('eng-changes-summary');

        function convertCommand() {
            const baseCmd = baseCmdInput.value;

            if (!baseCmd.trim()) {
                userOutputEl.innerHTML = '';
                engOutputEl.innerHTML = '';
                userChangesSummaryEl.innerHTML = '';
                engChangesSummaryEl.innerHTML = '';
                return;
            }
            
            // UPDATED REGEX: Made more flexible to support project names like 'a35x_mea_jv'
            const customerBlockRegex = /(\w+_(?:sea|swa|eur|cis|mea)_\w+)\s+([A-Z_ ]+?)(\s*)$/;

            let userChanges = [];
            let engChanges = [];

            let userHtml = baseCmd;
            if (userHtml.includes(' -ma ')) {
                userChanges.push(`<code class="summary-code">-ma</code> &rarr; <code class="summary-code color-mc">-mc</code>`);
                userHtml = userHtml.replace(' -ma ', ` <span class="highlight-mc">-mc</span> `);
            }
            
            const userMatch = userHtml.match(customerBlockRegex);
            if (userMatch) {
                const allCustomerCodes = userMatch[2];
                if (/OXT/.test(allCustomerCodes)) {
                    userChanges.push(`Customer &rarr; <code class="summary-code color-region">OLP</code>`);
                    userHtml = userHtml.replace(customerBlockRegex, `$1 <span class="highlight-region">OLP</span>$3`);
                } else if (/OLM|OJM|OXM|OXE|ODM|OLE/.test(allCustomerCodes)) {
                    userChanges.push(`Customer &rarr; <code class="summary-code color-region">OLE</code>`);
                    userHtml = userHtml.replace(customerBlockRegex, `$1 <span class="highlight-region">OLE</span>$3`);
                }
            }

            let engHtml = baseCmd;
            if (engHtml.includes(' -ma ')) {
                engChanges.push(`<code class="summary-code">-ma</code> &rarr; <code class="summary-code color-mc">-mc</code>`);
                engHtml = engHtml.replace(' -ma ', ` <span class="highlight-mc">-mc</span> `);
            }

            const engMatch = engHtml.match(customerBlockRegex);
            if (engMatch) {
                const allCustomerCodes = engMatch[2];
                if (/OXT/.test(allCustomerCodes)) {
                    if (!engChanges.includes(`Customer &rarr; <code class="summary-code color-region">OLP</code>`)) engChanges.push(`Customer &rarr; <code class="summary-code color-region">OLP</code>`);
                    engHtml = engHtml.replace(customerBlockRegex, `$1 <span class="highlight-region">OLP</span>$3`);
                } else if (/OLM|OJM|OXM|OXE|ODM|OLE/.test(allCustomerCodes)) {
                    if (!engChanges.includes(`Customer &rarr; <code class="summary-code color-region">OLE</code>`)) engChanges.push(`Customer &rarr; <code class="summary-code color-region">OLE</code>`);
                    engHtml = engHtml.replace(customerBlockRegex, `$1 <span class="highlight-region">OLE</span>$3`);
                }
            }

            if (baseCmd.includes(' -tuser ')) {
                engChanges.push(`<code class="summary-code">-tuser</code> &rarr; <code class="summary-code color-teng">-teng</code>`);
                engHtml = engHtml.replace(' -tuser ', ` <span class="highlight-teng">-teng</span> `);
            }
            if (baseCmd.match(/\s-d\s+(low)\s/)) {
                engChanges.push(`<code class="summary-code">-d low</code> &rarr; <code class="summary-code color-mid">-d mid</code>`);
            }
            engHtml = engHtml.replace(/\s-d\s+(low|mid)\s/, ` <span class="highlight-mid">-d mid</span> `);
            if (baseCmd.includes(' -s ')) {
                engChanges.push('Hapus <code class="summary-code">-s</code>');
                engHtml = engHtml.replace(' -s ', ' ');
            }

            if (userChanges.length > 0) {
                userChangesSummaryEl.innerHTML = '<strong>Perubahan:</strong><ul class="list-disc list-inside mt-1 space-y-1">' + userChanges.map(change => `<li>${change}</li>`).join('') + '</ul>';
            } else {
                userChangesSummaryEl.innerHTML = '<em>Tidak ada perubahan dari BASE.</em>';
            }
            if (engChanges.length > 0) {
                engChangesSummaryEl.innerHTML = '<strong>Perubahan:</strong><ul class="list-disc list-inside mt-1 space-y-1">' + engChanges.map(change => `<li>${change}</li>`).join('') + '</ul>';
            } else {
                engChangesSummaryEl.innerHTML = '<em>Tidak ada perubahan dari BASE.</em>';
            }

            revealText(userOutputEl, userHtml);
            revealText(engOutputEl, engHtml);
            userOutputContainer.classList.add('animate-magic-glow');
            engOutputContainer.classList.add('animate-magic-glow');
            setTimeout(() => {
                userOutputContainer.classList.remove('animate-magic-glow');
                engOutputContainer.classList.remove('animate-magic-glow');
            }, 600);
        }
        
        function revealText(element, htmlContent) {
            if (element.dataset.revealInterval) {
                clearInterval(parseInt(element.dataset.revealInterval));
            }
            element.innerHTML = htmlContent;
            const charSpans = [];
            function wrapChars(node) {
                if (node.nodeType === 3) {
                    const parent = node.parentNode;
                    const text = node.textContent;
                    const fragment = document.createDocumentFragment();
                    for (const char of text) {
                        const span = document.createElement('span');
                        span.textContent = char;
                        span.style.filter = 'blur(4px)';
                        span.style.transition = 'filter 0.15s ease';
                        fragment.appendChild(span);
                        charSpans.push(span);
                    }
                    parent.replaceChild(fragment, node);
                } else if (node.nodeType === 1) {
                    Array.from(node.childNodes).forEach(wrapChars);
                }
            }
            wrapChars(element);
            let i = 0;
            const interval = setInterval(() => {
                if (i < charSpans.length) {
                    charSpans[i].style.filter = 'none';
                    i++;
                } else {
                    clearInterval(interval);
                    element.dataset.revealInterval = null;
                }
            }, 10);
            element.dataset.revealInterval = interval.toString();
        }

        baseCmdInput.addEventListener('input', convertCommand);

        function copyToClipboard(element, event) {
            const textarea = document.createElement('textarea');
            textarea.value = element.textContent;
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                const toast = document.getElementById('toast-notification');
                toast.style.left = event.pageX + 'px';
                toast.style.top = event.pageY + 'px';
                toast.style.opacity = '1';
                toast.style.transform = 'translate(-50%, -150%)';
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translate(-50%, -120%)';
                }, 1500);
            } catch (err) {
                console.error('Gagal menyalin teks: ', err);
            }
            document.body.removeChild(textarea);
        }

        // Initial conversion on page load
        convertCommand();


        // --- NEURAL NETWORK BACKGROUND ANIMATION ---
        const canvas = document.getElementById('neural-network-canvas');
        const ctx = canvas.getContext('2d');
        let particlesArray;

        // Color palettes for themes
        const darkThemeColors = ['#08f7fe', '#05f7bf', '#b4f705', '#f78305', '#f7054d'];
        const lightThemeColors = ['#00A6FB', '#0583F2', '#05DBF2', '#04B2D9', '#037F8C'];
        let currentColors = document.documentElement.classList.contains('light') ? lightThemeColors : darkThemeColors;

        function setCanvasSize() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        setCanvasSize();

        class Particle {
            constructor(x, y, directionX, directionY, size, color) {
                this.x = x;
                this.y = y;
                this.directionX = directionX;
                this.directionY = directionY;
                this.size = size;
                this.color = color;
            }
            draw() {
                ctx.beginPath();
                // Add glow effect to particles
                ctx.shadowColor = this.color;
                ctx.shadowBlur = 15;
                ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2, false);
                ctx.fillStyle = this.color;
                ctx.fill();
                // Reset shadow after drawing to not affect other elements
                ctx.shadowBlur = 0;
            }
            update() {
                if (this.x > canvas.width || this.x < 0) this.directionX = -this.directionX;
                if (this.y > canvas.height || this.y < 0) this.directionY = -this.directionY;
                this.x += this.directionX;
                this.y += this.directionY;
                this.draw();
            }
        }
        
        function initAnimation() {
            particlesArray = [];
            let numberOfParticles = (canvas.height * canvas.width) / 10000;
            for (let i = 0; i < numberOfParticles; i++) {
                let size = (Math.random() * 2) + 1;
                let x = (Math.random() * ((innerWidth - size * 2) - (size * 2)) + size * 2);
                let y = (Math.random() * ((innerHeight - size * 2) - (size * 2)) + size * 2);
                let directionX = (Math.random() * .4) - .2;
                let directionY = (Math.random() * .4) - .2;
                // Pick a random color from the current palette
                let color = currentColors[Math.floor(Math.random() * currentColors.length)];
                particlesArray.push(new Particle(x, y, directionX, directionY, size, color));
            }
        }

        function connectParticles() {
            let opacityValue = 1;
            for (let a = 0; a < particlesArray.length; a++) {
                for (let b = a; b < particlesArray.length; b++) {
                    let distance = ((particlesArray[a].x - particlesArray[b].x) * (particlesArray[a].x - particlesArray[b].x)) +
                        ((particlesArray[a].y - particlesArray[b].y) * (particlesArray[a].y - particlesArray[b].y));
                    if (distance < (canvas.width / 8) * (canvas.height / 8)) {
                        opacityValue = 1 - (distance / 20000);
                        
                        // Use the color of the first particle for the line
                        const colorA = particlesArray[a].color;
                        
                        ctx.save();
                        ctx.globalAlpha = opacityValue;
                        ctx.strokeStyle = colorA;
                        ctx.lineWidth = 1;
                        ctx.beginPath();
                        ctx.moveTo(particlesArray[a].x, particlesArray[a].y);
                        ctx.lineTo(particlesArray[b].x, particlesArray[b].y);
                        ctx.stroke();
                        ctx.restore();
                    }
                }
            }
        }

        function animate() {
            requestAnimationFrame(animate);
            ctx.clearRect(0, 0, innerWidth, innerHeight);
            for (let i = 0; i < particlesArray.length; i++) {
                particlesArray[i].update();
            }
            connectParticles();
        }

        window.addEventListener('resize', () => {
            setCanvasSize();
            initAnimation();
        });


        initAnimation();
        animate();


        // --- THEME TOGGLE LOGIC ---
        const themeToggleBtn = document.getElementById('theme-toggle');
        const darkIcon = document.getElementById('theme-toggle-dark-icon');
        const lightIcon = document.getElementById('theme-toggle-light-icon');
        
        function applyTheme(isLight) {
             if (isLight) {
                document.documentElement.classList.add('light');
                lightIcon.classList.remove('hidden');
                darkIcon.classList.add('hidden');
            } else {
                document.documentElement.classList.remove('light');
                lightIcon.classList.add('hidden');
                darkIcon.classList.remove('hidden');
            }
            // Update animation colors after theme has been applied
            currentColors = isLight ? lightThemeColors : darkThemeColors;
            initAnimation(); // Re-initialize with new colors
        }

        const prefersLight = window.matchMedia('(prefers-color-scheme: light)').matches;
        const savedTheme = localStorage.getItem('theme');
        applyTheme(savedTheme === 'light' || (!savedTheme && prefersLight));
        
        themeToggleBtn.addEventListener('click', function() {
            const isCurrentlyLight = document.documentElement.classList.contains('light');
            localStorage.setItem('theme', isCurrentlyLight ? 'dark' : 'light');
            applyTheme(!isCurrentlyLight);
        });
    </script>
</body>
</html>