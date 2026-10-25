<script>
	
	
	$(document ).ready(function() {
		$(document).on('show.bs.modal','#modal-showlink',function(e){

			let link = $(e.relatedTarget).data('link')
			$('#modal-showlink .modal-body #link-par').text(link)

		})


	})

	function copyToClip(link){

		let aux = document.createElement("input");
		aux.setAttribute("value", link);
		document.body.appendChild(aux);
		aux.select();   
		document.execCommand("copy");
		document.body.removeChild(aux);

		swal({
			title: 'Link Copied',
			icon: 'success',
			button: {
				text: "OK",
				value: true,
				visible: true,
				className: "btn btn-success"
			}
		})

	}



	function expirar_link(cart_id) {

		swal({
			title: 'Are you sure?',
			icon: 'error',
			buttons: {
				confirm: {
					text: "Accept",
					value: true,
					visible: true,
					className: "sweet-warning",
					closeModal: true
				},
				cancel: {
					text: "Cancel",
					value: false,
					visible: true,
					className: "sweet-danager",
					closeModal: true,
				},

			},
		}).then((willDelete) => {

			$.ajax({
				url: '<?= company_url('cart/expire_link/') ?>' + cart_id,
				type:'get',
				dataType: 'json',
				success: function(response){

					location.reload()

				}
			})
			
		});
	}
</script>