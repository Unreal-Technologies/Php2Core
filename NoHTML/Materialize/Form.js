Math.decimals = function(number)
{
    return ((number - parseInt(number))+'').substr(2).length;
};

Math.roundFloat = function(number, decimals)
{
    let mul = Math.pow(10, decimals);
    return Math.round(number * mul) / mul;
};

class Form
{
    static validate(form)
    {
        let elements = $('#'+form.id+' input, #'+form.id+' select');
        let requiredCheck = true;
        
        for(let i=0; i<elements.length; i++)
        {
            let element = elements[i];
            let id = element.id;
            element.removeAttribute('data-error');
            element.classList.remove('valid');
            element.classList.remove('invalid');
            
            if(element.tagName === 'SELECT')
            {
                let parent = element.parentElement;
                
                for(let c=0; c<parent.children.length; c++)
                {
                    let child = parent.children[c];
                    if(child.tagName === 'INPUT')
                    {
                        child.classList.remove('valid');
                        child.classList.remove('invalid');
                        element = child;
                    };
                }
            }
            
            if(id === '')
            {
                continue;
            }

            let helper = $('.helper-text[for='+id+']')[0];
            if(element.name === element.id && element.required && element.value === '')
            {
                requiredCheck = false;
                element.classList.add('invalid');
                helper.setAttribute('data-error', 'This field is required.');

                continue;
            }
            
            element.classList.add('valid');
            helper.setAttribute('data-success', 'OK.');
        }
        
        if(!requiredCheck)
        {
            alert('One or more fields are invalid!');
        }
        
        return requiredCheck;
    };
    
    static initialize(form)
    {
        Form.#overrideNumbers(form);
    };
    
    static #overrideNumbers(form)
    {
        let elements = $('#'+form.id+' input[type=number]');
        
        for(let i=0; i<elements.length; i++)
        {
            let element = elements[i];
            element.type = 'text';
            element.onchange = function(event)
            {
                let target = event.target;
                let min = target.getAttribute('min');
                let max = target.getAttribute('max');
                let step = target.getAttribute('step');
                let value = target.value;
                
                if(step === null)
                {
                    step = 1;
                }
                
                if(min === null)
                {
                    min = Number.MIN_SAFE_INTEGER
                }
                
                if(max === null)
                {
                    max = Number.MAX_SAFE_INTEGER;
                }
                
                let isInt = parseFloat(step, 10) === parseInt(step, 10);
                let number = Math.roundFloat(isInt ? parseInt(value, 10) : parseFloat(value, 10), Math.decimals(step));
                
                target.value = number;
            };
            
//            let parent = element.parentNode;
//            
//            //<svg class="caret" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"></path><path d="M0 0h24v24H0z" fill="none"></path></svg>-->
//            
//            let up = document.createElement('svg');
//            up.setAttribute('class', 'caret');
//            up.setAttribute('height', '24');
//            up.setAttribute('viewBox', '0 0 24 24');
//            up.setAttribute('width', '24');
//            up.setAttribute('xmlns', 'http://www.w3.org/2000/svg');
//            up.innerHTML = '<path d="M7 10l5 5 5-5z"></path><path d="M0 0h24v24H0z" fill="none"></path>';
//            up.style.zIndex = -999;
//            up.style.position = 'absolute';
//            up.style.left = '0px';
//            up.style.top = '0px';
//            up.style.backgroundColor = 'red';
//            up.style.display = 'block';
//            up.style.width = '24px';
//            up.style.height = '24px';
//            
//            
//            parent.appendChild(up);
        }
    };
};