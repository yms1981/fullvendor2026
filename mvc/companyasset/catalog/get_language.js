const get_language = async () => {
    
    const response = await fetch(env.url+'/language',{
        method: 'GET'
    });
    
    const data_response = await response.json();
    env.lang = {...data_response}
    console.log(env)
}

// bring the language
document.addEventListener("DOMContentLoaded", async (event) => {
    
    //await get_language()
    
})