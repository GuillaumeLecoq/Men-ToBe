$(function() {
	$('#myWizard').easyWizard({
		buttonsClass: 'btn btn-default',
		submitButtonClass: 'btn btn-primary',
		showButtons: true,
		prevButton: "retour",
		nextButton: "suivant",
		submitButtonText: "enregistrer"
	});

	$('#myWizard .previous').bind('click', function(e) {
		e.preventDefault();
		$('.easyWizardButtons .prev').hide();
	});


	$('#myWizard .next').bind('click', function(e) {
		e.preventDefault();
		$('.easyWizardButtons .prev').show();
	});

});