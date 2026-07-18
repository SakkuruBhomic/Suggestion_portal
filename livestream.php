<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The New Silk Road | Live Stream Hub</title>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background: #f4f6f8;
            color: #2c3e50;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            min-height: 100vh;
        }

        /* Login Modal */
        .login-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            backdrop-filter: blur(4px);
        }

        .login-modal.hidden {
            display: none;
        }

        .login-box {
            background: white;
            border: 2px solid #1ca366;
            border-radius: 12px;
            padding: 40px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(28, 163, 102, 0.2);
        }

        .login-box h2 {
            font-size: 1.8rem;
            margin-bottom: 10px;
            text-align: center;
            color: #1ca366;
        }

        .login-box p {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 1rem;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            color: #2c3e50;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #1ca366;
            box-shadow: 0 0 10px rgba(28, 163, 102, 0.2);
            background: white;
        }

        .login-btn {
            width: 100%;
            padding: 14px;
            background: #1ca366;
            border: none;
            color: white;
            border-radius: 8px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .login-btn:hover {
            background: #147a4c;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(28, 163, 102, 0.3);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .error-message {
            color: #e74c3c;
            text-align: center;
            margin-top: 15px;
            font-size: 0.9rem;
            display: none;
            font-weight: 600;
        }

        .error-message.show {
            display: block;
        }

        .main-header {
            background: #1ca366;
            padding: 50px 20px;
            text-align: center;
            margin-bottom: 40px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .main-header h1 {
            font-size: 2.8rem;
            margin-bottom: 12px;
            color: white;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .subtitle {
            color: #e0f2e9;
            font-size: 1.1rem;
            font-weight: 500;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 40px 20px;
        }

        .streams-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stream-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .stream-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: #1ca366;
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .stream-card:hover {
            background: #fafafa;
            border-color: #1ca366;
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(28, 163, 102, 0.15);
        }

        .stream-card:hover::before {
            transform: scaleX(1);
        }

        .stream-card.active {
            border-color: #1ca366;
            background: #f0fbf5;
            box-shadow: 0 0 20px rgba(28, 163, 102, 0.15);
        }

        .stream-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }

        .stream-icon {
            font-size: 1.5rem;
            color: #1ca366;
        }

        .stream-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #2c3e50;
        }

        .stream-badge {
            display: inline-block;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            margin-left: auto;
        }

        .badge-fastest {
            background: linear-gradient(135deg, #1ca366, #147a4c);
        }

        .badge-premium {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .badge-ads {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .stream-description {
            color: #555;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 12px;
        }

        .stream-specs {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 0;
        }

        .spec {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            color: #555;
            background: #e9ecef;
            padding: 6px 12px;
            border-radius: 6px;
        }

        .player-section {
            margin-bottom: 30px;
        }

        .player-wrapper {
            width: 100%;
            aspect-ratio: 16 / 9;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        iframe, video {
            width: 100%;
            height: 100%;
            border: none;
            position: absolute;
            top: 0;
            left: 0;
        }

        #hls-video { display: none; }

        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10;
            display: none;
            text-align: center;
        }

        .loading.active {
            display: block;
        }

        .spinner {
            border: 4px solid #333;
            border-top: 4px solid #1ca366;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .status-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: white;
            border: 1px solid #e0e0e0;
            padding: 12px 16px;
            border-radius: 8px;
            margin-top: 12px;
            font-size: 0.9rem;
            flex-wrap: wrap;
            gap: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #1ca366;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .quality-info {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .quality-badge {
            background: #f4f6f8;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            border: 1px solid #e0e0e0;
            color: #2c3e50;
            font-weight: 600;
        }

        .controls-section {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .controls-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #2c3e50;
        }

        .controls-title i {
            color: #1ca366;
        }

        .controls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
        }

        button {
            padding: 12px 16px;
            cursor: pointer;
            background: #f4f6f8;
            border: 1px solid #dee2e6;
            color: #2c3e50;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            white-space: nowrap;
        }

        button:hover {
            background: #e9ecef;
            border-color: #1ca366;
            color: #1ca366;
            transform: translateY(-2px);
        }

        button.active {
            background: #1ca366;
            border-color: #1ca366;
            color: white;
            box-shadow: 0 4px 15px rgba(28, 163, 102, 0.3);
        }

        .info-box {
            background: #f0fbf5;
            border-left: 4px solid #1ca366;
            padding: 16px;
            border-radius: 8px;
            margin-top: 20px;
            color: #2c3e50;
            font-size: 0.95rem;
        }

        .info-box strong {
            color: #1ca366;
        }

        @media (max-width: 768px) {
            .main-header h1 { font-size: 2rem; }
            
            .streams-grid {
                grid-template-columns: 1fr;
            }

            .controls-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            button {
                padding: 10px 12px;
                font-size: 0.85rem;
            }

            .status-bar {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>

    <div class="login-modal" id="loginModal">
        <div class="login-box">
            <h2><i class="fas fa-lock"></i> Access Required</h2>
            <p>Welcome to The New Silk Road</p>
            
            <form id="loginForm" onsubmit="handleLogin(event)">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" placeholder="Enter username" required autocomplete="off">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" placeholder="Enter password" required autocomplete="off">
                </div>

                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>

                <div class="error-message" id="errorMessage"></div>
            </form>
        </div>
    </div>

    <div id="mainContent" style="display: none;">
        <header class="main-header">
            <h1><i class="fas fa-play-circle"></i> The New Silk Road</h1>
            <p class="subtitle">Premium Streaming Experience</p>
        </header>

        <div class="container">
            <div class="player-section">
                <div class="player-wrapper">
                    <div class="loading" id="loading">
                        <div class="spinner"></div>
                        <p style="color: white; margin-top: 10px; font-weight: 500;">Loading stream...</p>
                    </div>
                    <iframe id="stream-frame" allow="autoplay; fullscreen; encrypted-media" allowfullscreen title="Live Stream"></iframe>
                    <video id="hls-video" controls autoplay muted playsinline></video>
                </div>

                <div class="status-bar">
                    <div class="status-indicator">
                        <div class="status-dot"></div>
                        <span id="stream-status">Live</span>
                    </div>
                    <div class="quality-info">
                        <span id="stream-name" style="color: #1ca366; font-weight: 700;"></span>
                        <span class="quality-badge" id="quality-badge">720p</span>
                        <span class="quality-badge" id="source-badge">Fast</span>
                    </div>
                </div>
            </div>

            <div class="controls-section">
                <div class="controls-title">
                    <i class="fas fa-tv"></i> Select Stream Source
                </div>
                <div class="streams-grid">
                    <div class="stream-card active" onclick="selectStream(0, this)">
                        <div class="stream-header">
                            <i class="fas fa-rocket stream-icon"></i>
                            <span class="stream-title">VideoCDN</span>
                            <span class="stream-badge badge-fastest">FASTEST</span>
                        </div>
                        <p class="stream-description">⚡ Ultra-fast streaming with zero buffering</p>
                        <div class="stream-specs">
                            <span class="spec"><i class="fas fa-video"></i> 720p</span>
                            <span class="spec"><i class="fas fa-leaf"></i> Ad-Free</span>
                            <span class="spec"><i class="fas fa-bolt"></i> Instant</span>
                        </div>
                    </div>

                    <div class="stream-card" onclick="selectStream(1, this)">
                        <div class="stream-header">
                            <i class="fas fa-crown stream-icon"></i>
                            <span class="stream-title">Formula 1 TV</span>
                            <span class="stream-badge badge-premium">PREMIUM</span>
                        </div>
                        <p class="stream-description">🎬 <em>A moment of patience for a moment of perfection</em></p>
                        <div class="stream-specs">
                            <span class="spec"><i class="fas fa-gem"></i> 4K Available</span>
                            <span class="spec"><i class="fas fa-leaf"></i> Ad-Free</span>
                            <span class="spec"><i class="fas fa-stopwatch"></i> 1s Delay</span>
                        </div>
                    </div>

                    <div class="stream-card" onclick="selectStream(2, this)">
                        <div class="stream-header">
                            <i class="fas fa-play stream-icon"></i>
                            <span class="stream-title">StreamFree</span>
                            <span class="stream-badge badge-ads">WITH ADS</span>
                        </div>
                        <p class="stream-description">🎯 Quick streaming with occasional advertisements</p>
                        <div class="stream-specs">
                            <span class="spec"><i class="fas fa-video"></i> 720p</span>
                            <span class="spec"><i class="fas fa-ad"></i> Ad-Supported</span>
                            <span class="spec"><i class="fas fa-tachometer-alt"></i> Fast</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="controls-section">
                <div class="controls-title">
                    <i class="fas fa-sliders-h"></i> Quick Controls
                </div>
                <div class="controls-grid">
                    <button onclick="fullscreenToggle()" title="Toggle Fullscreen (F)">
                        <i class="fas fa-expand"></i> Fullscreen
                    </button>
                    <button onclick="toggleMute()" title="Toggle Mute (M)">
                        <i class="fas fa-volume-mute"></i> Mute
                    </button>
                    <button onclick="togglePlayPause()" title="Play/Pause (Space)">
                        <i class="fas fa-play"></i> Play
                    </button>
                    <button onclick="reloadStream()" title="Reload Stream (R)">
                        <i class="fas fa-redo"></i> Reload
                    </button>
                </div>
            </div>

            <div class="info-box">
                <strong><i class="fas fa-info-circle"></i> Tips:</strong> Choose your preferred stream quality and speed above. VideoCDN is recommended for the fastest experience. Use F for fullscreen, M for mute, and Space to play/pause.
            </div>
        </div>
    </div>

    <script>
        const VALID_USERNAME = 'IBOMMARAVI';
        const VALID_PASSWORD = 'IBOMMARAVI';

        const streams = [
            {
                name: 'VideoCDN - Fastest 720p',
                url: 'https://videocdn-4726.website/shopping2/?channel_id=sky_sport_f1_uk',
                type: 'iframe',
                quality: '720p',
                speed: 'Fastest',
                adFree: true
            },
            {
                name: 'Formula 1 TV - 4K Premium',
                url: 'https://amg12058-amg12058c1-vidaa-us-8693.playouts.now.amagi.tv/ts-us-w2-n2/playlist/amg12058-c15medianetworkslimitedfast-formula1tv-vidaaus/cb553e1e786c648798d43d65d8ef41a0dd243dfc087a8d6933fb4b926bc10f41e2e5af97b20cac7822fb0fdf61146d5a4d009247d8780ad7967cac48240d5734c6cdd52e8be24b4daddd0c2d34b07c0e49857671ad594f32d5e0110dc51ab07a0e1ca1494d5e2082b00cb27756cd9e0a957f378bbdbe306f2c180680585ee2be5da5edf55d478a6ba6e24948882a43e0805fc8d54aba9d860e9f159b23f340cb91a1ddd04687869369876d8d350d4b6c799c967090cc62a2c38a6c4bf71b41743b65ca82b0db6319dc3c2930f9fbd8f16cf912f19d3c916545d06af17da100a64a892c8ac414d6c7dcde465277a0ca4f134cca8c92afcb19129e43d687b3a2190c98f2073640cd7dca1d28379eeece1a90b17efcdf11a084453c1be5bc60c1dcd8f4eeb49e048a2ccec6f5a73c465d5c070d8849a79479f22daf1ec2aa4a71081b1ed9f030c582ca10271024ece96cf1233798f8aeaefa5446a00e1a5efb2edb490462c80b627de8288bf15605bb033aa9141ede7510da3f96d62df1b495f01504a5ccce54fe6cf6c761b89f308c5d10782196df090533261ad1ea7543397fc2378fb9a3d352392ccdd4e8d3e5097d6d4016561fc5f4ee749a8fff281905fe50296e6015198230cf79da72216a489f4e83fc5923df474b5fe7a32c135e6980c1b284b8d5c551bb6bc1d68f5245526b468d1f24054bfe93d72297544a7a6913811f747cb306574798fea38caba55ee2bd478f15474e17af9516451a1c7b1161f1f95af26eceb23e9cb6ef068e85fe13419672a7040697d3d953f4ec8fc05a0e97af25720dfee102f55f0ce432f90ed2af1ab6b151155238455ecb8d51c050aac9e8ac41f31e887f2f5fc95d96b9b9c4bc0e57e38204343719476514381f1e2988f6f4472556c9e6678c2ce7ef46e74344230054ca8d125bb9fd6510ee5e14099785fba78ce9263def34891fa76107b5190c3ef1f33aff8db6fffff5043e69eb939b609a82adace98cb24c170a6823d5d75e4063d0549be66ed3882d50ceaaaec8ff5c821f8d5d2d5b9b5486158e89d927a09494ff89187d3c7b648c0a736776a1d82b56b27aa12145b317a7969248eed7be0671a8288847f143dc5c5375b3c0d605c02aaae72a22f3956813bd1e5495b0af764b9976e548580c341020197dbc902feab6ae6a3cc214be285488664d68ad33d188b3626ef5bd29cd3087042a836d8dc4d55d1627e0c2266d1d04021106c6531c02d07e3bf554e2bfa286612ed855d14f27e92de68276b7b200ebc4c1f185ec2c2af2a45b58a159a743920bfaf4a3b478a85a6aaf28b61f61add095beebe4d5b8c2da510b4cce0acb479838ca869a9073071873915a681d66af4bb17e6339a3cd5bcea85a7fc68fad71a428600553447e9f63fdf6736f4a2c8eea78e7ad496a7b633ded834bf724bd4b0f8b4064ff7d4b170c0c464eb29daf4889c9bdf24751e966a305293b798010e746783877bdb86617/37/1920x1080_6431348/index.m3u8',
                type: 'hls',
                quality: '4K',
                speed: '1s Delay',
                adFree: true
            },
            {
                name: 'StreamFree - 720p Fast',
                url: 'https://streamfree.app/embed/racing/skyf1?server=origin&quality=1080p&category=racing',
                type: 'iframe',
                quality: '720p',
                speed: 'Fast',
                adFree: false
            }
        ];

        let currentStreamIndex = 0;
        let hls = null;
        const iframe = document.getElementById('stream-frame');
        const video = document.getElementById('hls-video');
        const loading = document.getElementById('loading');
        const loginModal = document.getElementById('loginModal');
        const mainContent = document.getElementById('mainContent');

        function handleLogin(event) {
            event.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const errorMessage = document.getElementById('errorMessage');

            if (username === VALID_USERNAME && password === VALID_PASSWORD) {
                loginModal.classList.add('hidden');
                mainContent.style.display = 'block';
                loadDefaultStream();
            } else {
                errorMessage.textContent = '❌ Invalid username or password. Please try again.';
                errorMessage.classList.add('show');
                document.getElementById('password').value = '';
            }
        }

        function loadDefaultStream() {
            selectStream(0, document.querySelectorAll('.stream-card')[0]);
        }

        function selectStream(index, element) {
            currentStreamIndex = index;
            const stream = streams[index];
            
            document.querySelectorAll('.stream-card').forEach(card => {
                card.classList.remove('active');
            });
            element.classList.add('active');

            document.getElementById('stream-name').textContent = stream.name;
            document.getElementById('quality-badge').textContent = stream.quality;
            document.getElementById('source-badge').textContent = stream.speed;

            loading.classList.add('active');

            if (stream.type === 'iframe') {
                showIframe(stream.url);
            } else {
                showVideo(stream.url);
            }
        }

        function showIframe(url) {
            video.style.display = 'none';
            iframe.style.display = 'block';
            iframe.src = url;
            if(hls) { hls.destroy(); hls = null; }
            setTimeout(() => loading.classList.remove('active'), 1000);
        }

        function showVideo(url) {
            iframe.style.display = 'none';
            video.style.display = 'block';
            
            if(hls) { hls.destroy(); }
            
            // HLS Configuration tuned for low latency / lagless viewing
            hls = new Hls({
                enableWorker: true,
                lowLatencyMode: true,
                liveSyncDurationCount: 3,
                liveMaxLatencyDurationCount: 10,
                maxBufferLength: 10
            });
            
            hls.loadSource(url);
            hls.attachMedia(video);
            hls.on(Hls.Events.MANIFEST_PARSED, function() {
                video.play();
                loading.classList.remove('active');
            });
            hls.on(Hls.Events.ERROR, function(event, data) {
                if (data.fatal) {
                    loading.classList.remove('active');
                }
            });
        }

        function fullscreenToggle() {
            const playerWrapper = document.querySelector('.player-wrapper');
            if (!document.fullscreenElement) {
                playerWrapper.requestFullscreen().catch(err => {
                    alert(`Error: ${err.message}`);
                });
            } else {
                document.exitFullscreen();
            }
        }

        function toggleMute() {
            video.muted = !video.muted;
        }

        function togglePlayPause() {
            if (video.paused) {
                video.play();
            } else {
                video.pause();
            }
        }

        function reloadStream() {
            selectStream(currentStreamIndex, document.querySelectorAll('.stream-card')[currentStreamIndex]);
        }

        document.addEventListener('keydown', function(event) {
            if (mainContent.style.display === 'none') return;
            
            switch(event.key.toLowerCase()) {
                case 'f':
                    fullscreenToggle();
                    break;
                case 'm':
                    toggleMute();
                    break;
                case ' ':
                    event.preventDefault();
                    togglePlayPause();
                    break;
                case 'r':
                    reloadStream();
                    break;
            }
        });
    </script>
</body>
</html>