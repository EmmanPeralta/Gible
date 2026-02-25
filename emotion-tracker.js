(function() {
  console.log("✅ Emotion Tracker script loaded");

  const els = {
    toggle: document.getElementById('et-toggle'),
    wrap: document.getElementById('et-wrap'),
    video: document.getElementById('et-video'),
    canvas: document.getElementById('et-overlay'),
    list: document.getElementById('et-emotion-list'),
    container: document.getElementById('emotion-tracker')
  };

  if (!els.toggle) {
    console.error("❌ Emotion tracker toggle button not found");
    return;
  }

  // Ensure the emotions list width matches content exactly
  if (els.list) {
    els.list.style.display = 'inline-block';
    els.list.style.width = 'fit-content';
    els.list.style.maxWidth = 'none';
    els.list.style.whiteSpace = 'nowrap';
    els.list.style.color = '#fff';
    els.list.style.padding = '8px 10px';
    els.list.style.borderRadius = '8px';
    els.list.style.boxShadow = '0 2px 6px rgba(0,0,0,0.15)';
    // Allow pointer events to pass through to underlying UI
    els.list.style.pointerEvents = 'none';
  }

  // Make the tracker visual elements non-interactive so clicks pass through
  if (els.wrap) {
    els.wrap.style.pointerEvents = 'none';
  }
  if (els.video) {
    els.video.style.pointerEvents = 'none';
  }
  if (els.canvas) {
    els.canvas.style.pointerEvents = 'none';
  }

  // Allow clicks to pass through the tracker container,
  // but keep the toggle button interactive.
  if (els.container) {
    els.container.style.pointerEvents = 'none';
  }
  if (els.toggle) {
    els.toggle.style.pointerEvents = 'auto';
    els.toggle.style.position = 'relative';
    els.toggle.style.zIndex = '1';
  }

  // Track latest top emotion globally
  window.etLatestEmotion = null;
  window.etLatestEmotionScore = 0;
  // Track session-best (highest confidence seen while running)
  window.etTopEmotionName = null;
  window.etTopEmotionScore = 0;
  // Final snapshotted values captured when quiz ends
  window.etFinalEmotionName = null;
  window.etFinalEmotionScore = 0;

  let running = false;
  let stream = null;
  let rafId = null;
  let modelsLoaded = false;

  // Multiple CDN sources for better reliability
  const MODEL_URLS = [
    "https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/weights",
    "https://unpkg.com/face-api.js@0.22.2/weights",
    "https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights"
  ];

  async function loadModels() {
    if (modelsLoaded) {
      console.log("ℹ️ Models already loaded");
      return;
    }

    for (let i = 0; i < MODEL_URLS.length; i++) {
      const url = MODEL_URLS[i];
      try {
        console.log(`🔄 Trying to load models from source ${i + 1}/${MODEL_URLS.length}: ${url}`);
        els.list.innerHTML = `<div style='color:#2196F3; padding:8px; background:#e3f2fd; border-radius:4px;'>🔄 Loading models from source ${i + 1}/${MODEL_URLS.length}...</div>`;
        await faceapi.nets.tinyFaceDetector.loadFromUri(url);
        console.log("✅ TinyFaceDetector loaded");
        await faceapi.nets.faceLandmark68Net.loadFromUri(url);
        console.log("✅ FaceLandmark68Net loaded");
        await faceapi.nets.faceExpressionNet.loadFromUri(url);
        console.log("✅ FaceExpressionNet loaded");
        modelsLoaded = true;
        console.log("✅ All models loaded successfully!");
        els.list.innerHTML = "<div style='color:#4CAF50; padding:8px; background:#e8f5e8; border-radius:4px;'>✅ Models loaded! Starting emotion detection...</div>";
        return; // Success
      } catch (e) {
        console.error(`❌ Failed to load from source ${i + 1}:`, e);
        if (i === MODEL_URLS.length - 1) {
          els.list.innerHTML = "<div style='color:#f44336; padding:8px; background:#ffebee; border-radius:4px;'>❌ Failed to load models from all sources. Please check your internet connection and try refreshing the page.</div>";
          throw e;
        }
      }
    }
  }

  async function startCamera() {
    if (stream) {
      console.log("ℹ️ Camera stream already active");
      return;
    }
    try {
      stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
      els.video.srcObject = stream;
      await els.video.play().catch(()=>{});
      console.log("✅ Webcam stream acquired");
    } catch (err) {
      console.error("❌ Webcam error:", err);
      throw err;
    }
  }

  async function detectLoop() {
    const ctx = els.canvas.getContext('2d');
    const opts = new faceapi.TinyFaceDetectorOptions();
    try {
      const detections = await faceapi
        .detectAllFaces(els.video, opts)
        .withFaceLandmarks()
        .withFaceExpressions();

      ctx.clearRect(0, 0, els.canvas.width, els.canvas.height);
      const displaySize = { width: els.video.width, height: els.video.height };
      faceapi.matchDimensions(els.canvas, displaySize);
      const resizedDetections = faceapi.resizeResults(detections, displaySize);

      if (resizedDetections.length > 0) {
        const d = resizedDetections[0];
        const box = d.detection.box;
        ctx.strokeStyle = "#00ff00";
        ctx.lineWidth = 2;
        ctx.strokeRect(box.x, box.y, box.width, box.height);
        const emotionData = Object.entries(d.expressions)
          .sort(([,a],[,b]) => b - a)
          .slice(0, 5);
        if (emotionData.length > 0) {
          window.etLatestEmotion = emotionData[0][0];
          window.etLatestEmotionScore = emotionData[0][1];
          // Update running-best if this frame exceeds the best score so far
          if (
            typeof window.etLatestEmotionScore === 'number' &&
            window.etLatestEmotionScore > (window.etTopEmotionScore || 0)
          ) {
            window.etTopEmotionName = window.etLatestEmotion;
            window.etTopEmotionScore = window.etLatestEmotionScore;
          }
        }
        els.list.innerHTML = emotionData.map(([emotion, conf], i) => {
          const pct = (conf * 100).toFixed(1);
          const color = i === 0 ? "#4CAF50" : i === 1 ? "#FF9800" : "#9E9E9E";
          return `
            <div style="margin:5px 0; padding:6px 0;">
              <strong style="color:${color}; font-size:16px;">${emotion.toUpperCase()}</strong>: 
              <span style="color:${color}; font-size:18px; font-weight:bold;">${pct}%</span>
            </div>`;
        }).join('');
      } else {
        window.etLatestEmotion = null;
        window.etLatestEmotionScore = 0;
        els.list.innerHTML = "<div style='color:#ff6b6b; padding:10px; background:#ffe6e6; border-radius:4px;'>No face detected</div>";
      }
    } catch (err) {
      console.error("❌ Detection error:", err);
      els.list.innerHTML = "<div style='color:#f44336; padding:10px; background:#ffebee; border-radius:4px;'>❌ Detection error</div>";
    }
    if (running) rafId = requestAnimationFrame(detectLoop);
  }

  async function start() {
    if (running) {
      console.log("ℹ️ Already running");
      return;
    }
    try {
      console.log("🔄 Starting emotion tracker...");
      els.wrap.style.display = 'block';
      els.toggle.textContent = "■ Stop Emotion Tracker";
      els.toggle.setAttribute('data-label', 'Stop Emotion Tracker');
      els.list.innerHTML = "<div style='color:#2196F3; padding:10px; background:#e3f2fd; border-radius:4px;'>🔄 Initializing...</div>";
      // Reset running-best at the start of a new session
      window.etTopEmotionName = null;
      window.etTopEmotionScore = 0;
      window.etFinalEmotionName = null;
      window.etFinalEmotionScore = 0;
      await loadModels();
      await startCamera();
      running = true;
      setTimeout(() => detectLoop(), 1000);
    } catch (e) {
      console.error("❌ Failed to start:", e);
      stop(true);
    }
  }

  function stop(fromError = false) {
    console.log("🛑 Stopping emotion tracker");
    running = false;
    if (rafId) { cancelAnimationFrame(rafId); rafId = null; }
    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
    const ctx = els.canvas.getContext('2d');
    ctx.clearRect(0, 0, els.canvas.width, els.canvas.height);
    els.toggle.textContent = "▶ Start Emotion Tracker";
    els.toggle.setAttribute('data-label', 'Start Emotion Tracker');
    if (fromError) {
      els.list.innerHTML = "<div style='color:#f44336; padding:10px; background:#ffebee; border-radius:4px;'>❌ Could not start. Verify camera permission and internet connection.</div>";
    } else {
      els.list.innerHTML = "<div style='color:#9E9E9E; padding:10px; background:#f5f5f5; border-radius:4px;'>⏹ Stopped</div>";
    }
  }

  els.toggle.addEventListener('click', () => {
    if (running) {
      stop();
    } else {
      start();
    }
  });

  // Expose controls/state to page scripts
  window.etStart = start;
  window.etStop = stop;
  window.etIsRunning = function() { return running; };
})();