
function isobackup_prepareUrl(new_parameters) {

	const url_parts = /^([^?]+)(?:\?(.*))?$/.exec(window.location.href);
	if (!url_parts) {
		throw new Error("isobackup_prepareUrl(): Can't determine URL parts");
	}

	const script = url_parts[1];
	const script_parameters = (url_parts.length > 2) ? url_parts[2].split('&').filter(p => p.substr(0, 7) !== 'action=').join('&') : "";

	return `${script}?` + (script_parameters ? `${script_parameters}&` : "") + new_parameters;
}

window.addEventListener("load", () => {

	const new_url = isobackup_prepareUrl("action=analysis");

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

function isobackup_update(item_id, action_index) {

	const new_url = isobackup_prepareUrl(`action=import-action&item=${item_id}&index=${action_index}`);

	const element = document.getElementById(`isobackup_updatebutton_${item_id}_${action_index}`);
	if (!element) {
		throw new Error("isobackup_update(): Can't locate button");
	}

	return fetch(new_url)
		.then(response => response.json())
		.then(result => {
			if (result.success) {
				const new_element = document.createElement("div");
				new_element.className = "done";
				new_element.innerHTML = "Done";
				element.replaceWith(new_element);
			}
			else {
				const new_element = document.createElement("div");
				new_element.className = "error";
				new_element.title = (result.message) ? result.message : "Unknown error";
				new_element.innerHTML = "Failed";
				element.replaceWith(new_element);
			}

		})
		.catch(error => {
			console.error(error);
		});
}

let isobackup_update_group_active = {};
let isobackup_update_group_remains = {};
function isobackup_update_group(group, totalnumber) {

	const new_url = isobackup_prepareUrl(`action=import-group&group=${group}`);

	const element = document.getElementById(`isobackup_updatebutton_${group}`);
	if (!element) {
		throw new Error("isobackup_update(): Can't locate button");
	}

	if (!isobackup_update_group_active.hasOwnProperty(group)) {
		isobackup_update_group_active[group] = false;
	}
	if (isobackup_update_group_active[group]) {
		console.log("deactivating...");
		element.className = null;
		isobackup_update_group_active[group] = false;
		return;
	}

	isobackup_update_group_active[group] = true;

	if (!isobackup_update_group_remains.hasOwnProperty(group)) {
		isobackup_update_group_remains[group] = totalnumber;
	}

	element.className = "busy";
	// element.disabled = true;

	const importGroup = function importGroup_recursive() {
		fetch(new_url)
			.then(response => response.json())
			.then(result => {
				if (result.success) {
					isobackup_update_group_remains[group]--;
					if (isobackup_update_group_remains[group] > 0) {
						element.innerHTML = element.innerHTML.replace(/\d+/, parseInt(isobackup_update_group_remains[group]));
						if (isobackup_update_group_active[group]) {
							window.setTimeout(importGroup_recursive, 10);
						}
					}
					else {
						element.remove();
						isobackup_update_group_active[group] = false;
						// const new_element = document.createElement("div");
						// new_element.className = "done";
						// new_element.innerHTML = "Done";
						// element.replaceWith(new_element);
					}
				}
				else {
					isobackup_update_group_active[group] = false;
					const new_element = document.createElement("div");
					new_element.className = "error";
					new_element.title = (result.message) ? result.message : "Unknown error";
					new_element.innerHTML = "Failed";
					element.replaceWith(new_element);
				}
			})
			.catch(error => {
				isobackup_update_group_active[group] = false;
				console.error(error);
			});
	};

	importGroup();
}

