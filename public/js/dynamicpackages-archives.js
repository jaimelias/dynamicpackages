jQuery(() => {
	'use strict';
	more_details_event();
	booking_filter();
});

const more_details_event = () => {
	
	jQuery('.dy_archive').find('a').click(function(e) {

		const title = jQuery(this).attr('title');
		const startingAt = parseInt(jQuery(this).attr('data-starting-at'));

		if(typeof gtag !== 'undefined' && startingAt)
		{
			//send to analytics only
			sendGa4Event( 'view_item', {
				currency: 'USD',
				value: startingAt,
				items : [title]
			});
		}
		
		if(typeof fbq !== 'undefined')
		{
			fbq('track', 'ViewContent');
		}
	});
}

const booking_filter = () => {

	jQuery('#dy_package_filter_form').each(function(){
		const thisForm = jQuery(this);

		const nullParams = {
			location: 'any',
			category: 'any',
			sort: 'any',
			keywords: ''
		};

		jQuery(thisForm).find('select').each(function(){
			const thisField = jQuery(this);
			const countOptions = jQuery(thisField).find('option').length;
			
			if(countOptions <= 1)
			{
				jQuery(thisField).prop('disabled', true);
			}
		});

		/**
		 * Handles select changes and decides whether to submit the complete
		 * search form or navigate directly to a single taxonomy URL.
		 *
		 * @param {Event} event jQuery change event.
		 * @returns {void}
		 */
		jQuery(thisForm).find('select').on('change', event => {
			const $select = jQuery(event.currentTarget);
			const formData = jQuery(thisForm).serializeArray();

			const thisValue = $select.val();
			const thisName = $select.attr('name');

			if (['location', 'category', 'sort'].includes(thisName)) {
				if (typeof sendGa4Event === 'function') {
					sendGa4Event('search', {
						search_term: `${thisName}-${thisValue}`,
					});
				}

				if (typeof fbq === 'function') {
					fbq('track', 'Search');
				}
			}

			let countAllChanges = 0;
			const taxChanges = [];

			formData.forEach(({name, value}) => {
				if (value === nullParams[name]) {
					return;
				}

				if (['location', 'category'].includes(name)) {
					taxChanges.push({name, value});
				}

				countAllChanges++;
			});

			const shouldRedirectTaxonomy =
				countAllChanges === 1
				&& taxChanges.length === 1
				&& taxChanges[0].value !== 'any';

			if (!shouldRedirectTaxonomy) {
				createFormSubmit(thisForm);
				return;
			}

			const [{name, value}] = taxChanges;

			const homeUrl = new URL(
				jQuery(thisForm).attr('data-home-url')
			);

			const pathnameArr = homeUrl.pathname
				.split('/')
				.filter(Boolean);

			pathnameArr.push(`package_${name}`, value);

			homeUrl.pathname = `/${pathnameArr.join('/')}/`;

			window.location.href = homeUrl.href;
		});
	});
	

}