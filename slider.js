(function(){
  // Reusable lesson slider with smooth fading image transitions
  window.mountLessonSlider = function mountLessonSlider({ slides, quizHref }) {
    var current = 0;
    var isAnimating = false;
    var slideBox = document.getElementById('slide-box'); // has class .slide
    var slideCounter = document.getElementById('slide-counter');
    var nextSlideContainer = document.getElementById('next-slide-container');

    if (!slideBox || !slideCounter) return;

    // Persistent wrappers so only the image source changes
    var wrapper = document.createElement('div');
    wrapper.className = 'slide-wrapper';
    wrapper.style.position = 'relative';
    var imgEl = document.createElement('img');
    imgEl.loading = 'lazy';
    imgEl.alt = '';
    imgEl.style.borderRadius = '10px';
    imgEl.style.display = 'block';
    var ttsButton = document.createElement('button');
    ttsButton.type = 'button';
    ttsButton.className = 'slide-tts-button';
    ttsButton.setAttribute('aria-label', 'Read slide aloud');
    ttsButton.setAttribute('data-tts-trigger', 'click');
    ttsButton.dataset.ttsSkip = '1';
    ttsButton.innerHTML = '&#128266;';
    var btnStyle = ttsButton.style;
    btnStyle.position = 'absolute';
    btnStyle.top = '8px';
    btnStyle.right = '8px';
    btnStyle.width = '38px';
    btnStyle.height = '38px';
    btnStyle.display = 'flex';
    btnStyle.alignItems = 'center';
    btnStyle.justifyContent = 'center';
    btnStyle.border = 'none';
    btnStyle.borderRadius = '50%';
    btnStyle.background = 'rgba(0, 0, 0, 0.55)';
    btnStyle.color = '#fff';
    btnStyle.cursor = 'pointer';
    btnStyle.fontSize = '18px';
    btnStyle.lineHeight = '1';
    btnStyle.transition = 'background 0.2s ease';
    ttsButton.onmouseenter = function(){ btnStyle.background = 'rgba(0, 0, 0, 0.7)'; };
    ttsButton.onmouseleave = function(){ btnStyle.background = 'rgba(0, 0, 0, 0.55)'; };
    ttsButton.addEventListener('click', function(e){
      e.stopPropagation();
      e.preventDefault();
      speakCurrentSlide();
    });
    wrapper.appendChild(imgEl);
    wrapper.appendChild(ttsButton);
    slideBox.appendChild(wrapper);

    function updateMeta(idx) {
      slideCounter.textContent = (idx + 1) + '/' + slides.length;
      slideCounter.setAttribute('data-label', 'Slide ' + (idx + 1) + ' of ' + slides.length);
    }

    function speakCurrentSlide() {
      var slide = slides[current];
      if (!slide || !slide.label) return;
      if (typeof window.manualTTSSpeak === 'function') {
        window.manualTTSSpeak(slide.label);
      } else if (window.speechSynthesis && typeof SpeechSynthesisUtterance !== 'undefined' && window.ttsEnabled === 1) {
        var utter = new SpeechSynthesisUtterance(slide.label);
        window.speechSynthesis.cancel();
        window.speechSynthesis.speak(utter);
      }
    }

    function render(idx) {
      if (idx < 0) idx = 0;
      if (idx >= slides.length) idx = slides.length - 1;
      if (idx === current) return; // no change
      current = idx;

      var slide = slides[idx];

      // Proceed button only changes when final state reached
      if (idx === slides.length - 1 && quizHref) {
        if (!nextSlideContainer.innerHTML) {
          nextSlideContainer.innerHTML = '<a href="' + quizHref + '" style="text-decoration:none;">\n            <button class="proceedquiz-btn speak-on-hover" data-label="Proceed to Quiz">Proceed to Quiz</button>\n          </a>';
        }
      } else {
        if (nextSlideContainer.innerHTML) nextSlideContainer.innerHTML = '';
      }

      // Instant swap after preload (no fade)
      if (isAnimating) return;
      isAnimating = true;
      var newSrc = slide.img;
      var preload = new Image();
      preload.onload = function(){
        // After preload complete, swap source instantly
        imgEl.src = newSrc;
        wrapper.setAttribute('data-label', slide.label || '');
        isAnimating = false;
        if (typeof attachTTSListeners === 'function') attachTTSListeners();
      };
      preload.src = newSrc;

      // Preload adjacent images (next & previous) to reduce flicker
      var nextIdx = idx + 1;
      var prevIdx = idx - 1;
      if (slides[nextIdx]) { var nImg = new Image(); nImg.src = slides[nextIdx].img; }
      if (slides[prevIdx]) { var pImg = new Image(); pImg.src = slides[prevIdx].img; }

      updateMeta(idx);
    }

    // Initial state (no fade needed)
    var first = slides[0];
    imgEl.src = first.img;
    wrapper.setAttribute('data-label', first.label || '');
    updateMeta(0);
    if (typeof attachTTSListeners === 'function') attachTTSListeners();

    // Click-to-navigate: left half = previous, right half = next
    slideBox.onclick = function(e){
      if (isAnimating) return; // avoid spam clicks mid-transition
      var rect = slideBox.getBoundingClientRect();
      var x = e.clientX - rect.left;
      if (x < rect.width / 2) {
        if (current > 0) render(current - 1);
      } else {
        if (current < slides.length - 1) render(current + 1);
      }
    };
  };
})();