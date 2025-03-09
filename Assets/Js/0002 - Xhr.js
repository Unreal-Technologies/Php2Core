class Xhr 
{
    /**
     * @type Boolean
     */
    static #bLoading = false;
    
    /**
     * @type Boolean
     */
    static #bLast = false;
    
    /**
     * @param {string} sUrl
     * @param {function} oSuccess
     * @param {function} oFail
     * @returns {void}
     */
    static get(sUrl, oSuccess, oFail)
    {
        //call ajax
        this.#wrapper('GET', sUrl, null, oSuccess, oFail);
    };

    /**
     * @param {string} sUrl
     * @param {string} sData
     * @param {function} oSuccess
     * @param {function} oFail
     * @returns {void}
     */
    static post(sUrl, sData, oSuccess, oFail)
    {
        //call ajax
        Xhr.#wrapper('POST', sUrl, sData, oSuccess, oFail);
    };
    
    /**
     * @param {function} oCallback
     * @returns {function}
     */
    static loading(oCallback)
    {
        //check loading state
        Xhr.#checkState(oCallback);
    };
    
    /**
     * @param {function} oCallback
     * @returns {void}
     */
    static #checkState(oCallback)
    {
        //keep checking every 50 ms for a state change, if result is returned within a 50 ms interval, loading is not triggers
        setTimeout(function()
        {
            //get current states
            let bLast = Xhr.#bLast;
            let bLoading = Xhr.#bLoading;
            
            //check state
            if(bLast !== bLoading)
            {
                //set new state
                Xhr.#bLast = bLoading;
                
                //do callback
                oCallback(bLoading);
            }
            
            //Call itself
            Xhr.#checkState(oCallback);
        }, 200);
    }

    /**
     * @param {string} sType
     * @param {string} sUrl
     * @param {string} sData
     * @param {function} oSuccess
     * @param {function} oFail
     * @returns {void}
     */
    static #wrapper(sType, sUrl, sData, oSuccess, oFail, bSetState = false)
    {
        if(bSetState)
        {
            Xhr.#bLoading = true;
        }
        Xhr.#ajax(
            sType, 
            sUrl, 
            sData, 
            function(sData)
            {
                oSuccess(sData);
                if(bSetState)
                {
                    Xhr.#bLoading = false;
                }
            }, 
            function(sData)
            {
                try
                {
                    oFail(sData);
                }
                catch(ex)
                {
                    alert('Request Failed, see console log for details');
                    console.log(sData);
                    console.log(ex);
                }
                if(bSetState)
                {
                    Xhr.#bLoading = false;
                }
            }
        );
    }

    /**
     * @param {string} sType
     * @param {string} sUrl
     * @param {string} sData
     * @param {function} oSuccess
     * @param {function} oFail
     * @returns {void}
     */
    static #ajax(sType, sUrl, sData, oSuccess, oFail)
    {
        //do ajax call
        $.ajax({
            type: sType,
            url: sUrl,
            data: sData,
            success: function(sData)
            {
                try
                {
                    //try success
                    oSuccess(sData);
                }
                catch(ex)
                {
                    console.log(ex);
                    oFail(sData);
                }
            },
            fail: function(sData)
            {
                //call failed on error
                oFail(sData, null);
            }
        });
    };
};
