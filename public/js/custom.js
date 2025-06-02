
/* use ajax to submit the HVAC form*/
window.addEventListener('load', function(event) {
    document.getElementById("hvac_type_form_Number_of_rooms").addEventListener("change", function(e) {
        showExtraInputs(e);
    });
    document.getElementById("hvac_type_form_Number_of_rooms").addEventListener("keyup", function(e) {
        showExtraInputs(e);
    });

    document.getElementById("hvacform_demo").addEventListener("submit", function(e) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);
        var hvactitle = document.getElementById('hvactitle');
        const alerrtdiv =  document.getElementById('alertdiv');
        const data = {};

        hvactitle.classList.add('d-none');
        alerrtdiv.classList.add('d-none');
        // Trigger Bootstrap 5 modal
        const modal = new bootstrap.Modal(document.getElementById('loaidngmodal'));
        modal.show();

        formData.forEach((value, fullKey) => {
            // Match Symfony-style keys like hvac_type_form[field]
            const match = fullKey.match(/^([^\[]+)\[([^\]]+)\]$/);
            if (!match) return;

            const base = match[1];         // e.g., hvac_type_form
            const key = match[2];          // e.g., cooling_capacity

            // Initialize structure
            if (!data[base]) {
                data[base] = {};
            }

            // For fields that must always be arrays
            if (key === 'cooling_capacity' || key === 'indoor_unit_type') {
                if (!Array.isArray(data[base][key])) {
                    data[base][key] = [];
                }
                data[base][key].push(value);
            } else {
                // For normal fields, just assign value
                data[base][key] = value;
            }
        });

        // console.log(data)
        // return;

        fetch('/api/hvac', {
           method: 'POST',
           headers: {
               'Content-Type': 'application/json'
           },
           body: JSON.stringify(data)
       })
       .then(res => res.json())
       .then(data => {
           if (data.success) {

                hvactitle.classList.remove('d-none');
               renderHVACCombinations(data.combinations);
               closeMyModal();

           } else {
               console.error('Errors:', data.errors);
               closeMyModal();
           }
       })
       .catch(err => {
           console.error('Request failed', err);
           closeMyModal();
       });

    })

});

function showExtraInputs(e) {
    var rooms_nr = parseInt(e.target.value) || 0;

    const container = document.getElementById('aditions');
    const template = document.getElementById('grouptemplate');

     container.innerHTML = '';
     if( rooms_nr > 1 && rooms_nr < 6 ) {
         template.classList.remove('d-none');
         for (let i = 1; i < rooms_nr; i++) {
             const clone = template.cloneNode(true);
             container.appendChild(clone);
         }

     } else if( rooms_nr > 0 && rooms_nr < 2 ) {
         template.classList.remove('d-none');
     } else {
         template.classList.add('d-none');

     }
}

function renderHVACCombinations(combinations) {
    const container = document.querySelector('.havc-combinations-response');
    const template = document.getElementById('product-combination-template');
    const alerrtdiv =  document.getElementById('alertdiv');

    container.innerHTML = '';

    if( combinations.length > 0 ) {
        combinations.forEach((combo, index) => {
            const comboGroup = document.createElement('div');
            comboGroup.classList.add('combination-group');

            combo.forEach(product => {
                const clone = template.cloneNode(true);
                clone.classList.remove('d-none');

                clone.querySelector('.name').textContent = product.name || '';
                clone.querySelector('.cooling_capacity').textContent = product.cooling_capacity || '';
                clone.querySelector('.type').textContent = product.type || '';
                clone.querySelector('.brand').textContent = product.brand || '';

                comboGroup.appendChild(clone);
            });

            container.appendChild(comboGroup);
        });
    } else {

        alerrtdiv.classList.remove('d-none');

    }

}

function closeMyModal() {
  const modalEl = document.getElementById('loaidngmodal');
  const modal = bootstrap.Modal.getInstance(modalEl); // Get the existing instance
  if (modal) {
    modal.hide(); // Close the modal
  }
}
/* end of use ajax to submit the HVAC form*/
