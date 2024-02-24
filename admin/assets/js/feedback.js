document.addEventListener('DOMContentLoaded', function () {
	// show overlay when clicked on "deactivate" for any known ISC slug
	let iscDeactivateLinks = document.querySelectorAll('#deactivate-isc, #deactivate-image-source-control-isc, #deactivate-image-source-control');

	iscDeactivateLinks.forEach(link => {
		link.addEventListener('click', function (e) {
			// bail if there is more than one deactivate link (ISC being enabled more than once)
			if (document.querySelectorAll('#deactivate-isc, #deactivate-image-source-control-isc, #deactivate-image-source-control').length > 1) {
				return;
			}
			e.preventDefault();
			// update the global variable with the href of the clicked link
			iscDeactivateLinkUrl = this.getAttribute( 'href' );
			// only show the feedback form once per 30 days
			var cValue = iscFeedbackGetCookie("isc_hide_deactivate_feedback");
			if (cValue === undefined) {
				document.querySelector('#isc-feedback-overlay').style.display = 'block';
				document.querySelector('#isc-feedback-content textarea').focus();
			} else {
				// click on the deactivate link
				window.location.href = iscDeactivateLinkUrl;
			}
		});
	});

	// show the email input field
	document.querySelector('#isc-feedback-send-reply').addEventListener('click', function () {
		document.querySelector('#isc-feedback-reply-email').style.display = 'block';
	});

	// send the form or close it
	document.querySelectorAll('#isc-feedback-content .button').forEach(button => {
		button.addEventListener('click', function (e) {
			e.preventDefault();
			// set the cookie for 30 days
			iscStoreFeedbackCookie();

			// hide the content of the feedback form while we process the data
			document.querySelector('#isc-feedback-content form').style.display = 'none';
			if (this.classList.contains('isc-feedback-submit')) {
				// show feedback message
				document.querySelector('#isc-feedback-after-submit').style.display = 'block';

				// Preparing form data including the action parameter
				let formData = new FormData(document.querySelector('#isc-feedback-content form'));
				formData.append('action', 'isc_send_feedback'); // Adding the action parameter manually

				fetch(ajaxurl, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
					},
					body: new URLSearchParams(formData).toString() // Convert FormData to URLSearchParams string
				})
					.finally(() => {
						// deactivate the plugin and close the popup with a timeout
						setTimeout(() => {
							document.querySelector('#isc-feedback-overlay').remove();
						}, 2000)
						window.location.href = iscDeactivateLinkUrl;
					});
			} else {
				document.querySelector('#isc-feedback-overlay').remove();
				window.location.href = iscDeactivateLinkUrl;
			}
		});
	});


	// close button for feedback form
	document.querySelector('#isc-feedback-overlay-close-button').addEventListener('click', function () {
		document.querySelector('#isc-feedback-overlay').style.display = 'none';
	});
});

function iscFeedbackGetCookie(name) {
	const cookies = document.cookie.split(';');
	for (let cookie of cookies) {
		const [cookieName, cookieValue] = cookie.split('=').map(c => c.trim());
		if (cookieName === name) {
			return decodeURIComponent(cookieValue);
		}
	}
}

function iscStoreFeedbackCookie() {
	const expiry_date = new Date();
	expiry_date.setSeconds(expiry_date.getSeconds() + 2592000);
	document.cookie = "isc_hide_deactivate_feedback=1; expires=" + expiry_date.toUTCString() + "; path=/";
}
