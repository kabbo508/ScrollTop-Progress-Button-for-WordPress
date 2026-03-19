(function(){
	if(typeof STPB === "undefined") return;
	var cfg = STPB;

	var wrap = document.querySelector(".stpb-wrap");
	if(!wrap) return;

	var btn = wrap.querySelector(".stpb-btn");
	var circle = wrap.querySelector(".stpb-ring .progress");
	var circumference = 0;

	function setupCircle(){
		if(!circle) return;
		var r = circle.r && circle.r.baseVal ? circle.r.baseVal.value : 0;
		circumference = 2 * Math.PI * r;
		circle.style.strokeDasharray = circumference + " " + circumference;
		circle.style.strokeDashoffset = circumference;
	}

	function getScrollPercent(){
		var doc = document.documentElement;
		var scrollTop = window.pageYOffset || doc.scrollTop || 0;
		var scrollHeight = (doc.scrollHeight || 0) - (doc.clientHeight || 0);
		if(scrollHeight <= 0) return 0;
		return Math.min(1, Math.max(0, scrollTop / scrollHeight));
	}

	var ticking = false;
	function onScroll(){
		if(ticking) return;
		ticking = true;
		window.requestAnimationFrame(function(){
			var doc = document.documentElement;
			var scrollTop = window.pageYOffset || doc.scrollTop || 0;

			if(scrollTop >= cfg.showAfter){
				wrap.classList.add("is-visible");
			}else{
				wrap.classList.remove("is-visible");
			}

			if(cfg.showProgress && circle && circumference){
				var p = getScrollPercent();
				circle.style.strokeDashoffset = String(circumference - (p * circumference));
			}
			ticking = false;
		});
	}

	function smoothScrollToTop(){
		var start = window.pageYOffset || document.documentElement.scrollTop || 0;
		var startTime = null;

		function step(ts){
			if(!startTime) startTime = ts;
			var elapsed = ts - startTime;
			var t = Math.min(1, elapsed / cfg.smoothMs);
			var ease = 1 - Math.pow(1 - t, 3);
			window.scrollTo(0, Math.round(start * (1 - ease)));
			if(t < 1){
				window.requestAnimationFrame(step);
			}
		}
		window.requestAnimationFrame(step);
	}

	btn.addEventListener("click", function(e){
		e.preventDefault();
		smoothScrollToTop();
	});

	setupCircle();
	onScroll();
	window.addEventListener("scroll", onScroll, { passive: true });
	window.addEventListener("resize", function(){ setupCircle(); onScroll(); }, { passive: true });
})();
