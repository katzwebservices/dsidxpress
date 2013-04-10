dsIDXpressFilters = {
	FillHiddenWithValues: function (divID, hiddenID) {
		var f = document.getElementById(divID), g = f.childNodes, s = '', i;
		for (i = 0; i < g.length; i++) {
			if (g[i].checked) { s == '' ? s = s + g[i].value : s = s + '\n' + g[i].value; }
		}
		document.getElementById(hiddenID).value = s;
	},
	FillHiddenWithSelected: function (divID, hiddenID) {
		var f = document.getElementById(divID), g = f.childNodes, s = '', i;
		for (i = 0; i < g.length; i++) {
			if (g[i].selected) { s == '' ? s = s + g[i].value : s = s + '\n' + g[i].value; }
		}
		document.getElementById(hiddenID).value = s;
	}
}

jQuery(document).ready(function () {
    jQuery(".dsidxpress-proptype-filter").click(function () { dsIDXpressFilters.FillHiddenWithValues('dsidxpress-property-types', 'dsidxpress-RestrictResultsToPropertyType'); });
    jQuery(".dsidxpress-states-filter").click(function () { dsIDXpressFilters.FillHiddenWithSelected('dsidxpress-states', 'dsidxpress-RestrictResultsToState'); });
});