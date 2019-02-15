
window.addEventListener("load", () => {

	const url_parts = /^([^?]+)(?:\?(.*))?$/.exec(window.location.href);
	if (!url_parts) {
		return;
	}

	const e_analysis = document.getElementById("tl_isobackup_analysis");
	if (e_analysis == null) {
		return;
	}
	while (e_analysis.firstChild) {
		e_analysis.removeChild(e_analysis.firstChild);
	}

	const progressbar = document.createElement("div");
	progressbar.className = "progressbar";
	progressbar.setAttribute("style", "display: none");
	const progress = document.createElement("div");
	progress.className = "progress";
	progress.setAttribute("style", "width: 0%");
	progressbar.appendChild(progress);
	e_analysis.appendChild(progressbar);

	const messages = document.createElement("div");
	messages.className = "messages";
	e_analysis.appendChild(messages);

	const script = url_parts[1];
	const script_parameters = url_parts[2].split('&').filter(p => p.substr(0, 7) !== 'action=').join('&');

	let new_url = script;
	if (script_parameters !== '') {
		new_url += `?${script_parameters}&action=analysis`;
	}
	else {
		new_url += `?action=analysis`;
	}

	const analyze = function analyze_recursive(url_prefix, step = "init") {
		fetch(url_prefix + "&step=" + step)
			.then(response => response.json())
			.then(result => {
				if (result.message) {
					item = document.createElement("div");
					item.innerHTML = result.message;
					messages.appendChild(item);
				}
				if (result.progress || result.progress === 0) {
					progressbar.setAttribute("style", "display: flex");
					progressbar.setAttribute("title", Math.floor(result.progress) + "%");
					progress.setAttribute("style", "width: " + result.progress + "%");
				}
				if (result.next_step) {
					window.setTimeout(analyze_recursive, 10, url_prefix, result.next_step);
				}
			})
			.catch(error => {
				console.error(error);
			});
	};

	analyze(new_url);
});
