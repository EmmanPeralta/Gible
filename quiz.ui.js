(function(){
  function extractAnswer(opt, idx){
    if (/<img/i.test(opt)) return 'image ' + (idx + 1);
    var dashIdx = opt.indexOf('-');
    return dashIdx !== -1 ? opt.substring(dashIdx + 1).trim() : opt.trim();
  }

  function computeTTSLabel(questionText, options){
    var labels = [
      'Triangle for ' + extractAnswer(options[0], 0),
      'Circle for ' + extractAnswer(options[1], 1),
      'Cross for ' + extractAnswer(options[2], 2),
      'Square for ' + extractAnswer(options[3], 3)
    ];
    return questionText + '. ' + labels.join(', ') + '.';
  }

  function addLazyToImgs(html){
    // Insert loading="lazy" into any <img ...> tags
    return html.replace(/<img\s+/gi, '<img loading="lazy" ');
  }

  function buildOptionsMarkup(options){
    var hasImages = options.some(function(o){ return /<img/i.test(o); });
    if (hasImages){
      return '<div style="display:flex; gap:12px; justify-content:center; align-items:flex-end; flex-wrap:nowrap;">'
        + options.map(function(opt){
            return '<div style="display:flex; flex-direction:column; align-items:center; text-align:center;">'
              + addLazyToImgs(opt) + '</div>';
          }).join('')
        + '</div>';
    }
    return options.map(function(opt){
      return '<div style="text-align:left;margin-left:1em;">' + opt + '</div>';
    }).join('');
  }

  window.quizUtils = {
    computeTTSLabel: computeTTSLabel,
    buildOptionsMarkup: buildOptionsMarkup
  };
})();