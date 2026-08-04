function echapperHTML(texte){
    const div = document.createElement('div');
    div.textContent = texte;
    return div.innerHTML;

}
//script de connexion des comptes administrateurs

function initLoginForm() {
  const form = document.getElementById("form-login");
  if (!form) return;
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const email = document.getElementById("idendtifiant").value.trim();
    const mot_de_passe = document.getElementById("mot_de_passe").value;
// requête vers le fichier php de connxion du site
    const res = await apiFetch("../../api/auth/login.php", {
      method: "POST",
      body: { 
        email,
        mot_de_passe
     },
    });
    if (res.success) {
      setCurrentUser(res.user, res.token);
      window.location.href = "dashboard.html";
    } else {
      showFormMsg("login-msg", res.message, "error");
    }
  });
}
// script de recup des stats du site
function recup_stats(){

    let nb_events = 0;
    let nb_users = 0;
    let nb_messages = 0;
    const events = document.getElementById("event-count")
    const users = document.getElementById("user-count")
    const messages = document.getElementById("message-count")

    const res = await apiFetch("../../api/back-office/dashboard-admin.php",{
        method: "get",
    })
    
    if(res.success){
        nb_events = res.events;
        nb_users = res.users;
        nb_messages = res.messages;

        events.textContent = nb_users;
        users.textContent = nb_users;
        messages.textContent = nb_messages;
    }
}

//script de recuperation des evenements 
function recup_events(){

    let events = [];

    const show = document.getElementById("show-events");
    const res = await apiFetch("", {
        method: "get"
    });

    if(res.success){
        events = res.events;
        const carte = events.map(ligne => `
            <div class="cartes">
                <h3>{$echapperHTML(ligne.nom_events)}</h3>
                <p>{$echapperHTML(ligne.nom)}</p>
                <p>{$echapperHTML(ligne.description)}</p>
                <p>{$echapperHTML(ligne.statut)}</p>
                <p>{$echapperHTML(ligne.nom)}</p>
                <p>{$echapperHTML(ligne.nb_messages)}</p>
                <button id="supprimer">Supprimer</button>
            </div>
            `)
    }else{
        show.textContent("Aucun evenements pour l'instant")
    }

}
//fonction de recuperation des utilisateurs
function recup_users(){
    const div = document.getElementById("users_container");
    let users = [];

    const res = await apiFetch("", {
        method:"get"
    })

    if(res.success){
        users = res.users;
         const carte = users.map(ligne => `
            <div class="lignes">
                <p>{$echapperHTML(ligne.nom)}</p>
                <p>{$echapperHTML(ligne.email)}</p>
                <p>{$echapperHTML(ligne.plan)}</p>
                <p>{$echapperHTML(ligne.plan_expire)}</p>
                <p>{$echapperHTML(ligne.suspendu)}</p>
                <button id="supprimer">Supprimer</button>
                <button id="voir"> Voir les evenements </button>
            </div>
            `)
    }else{
        div.textContent("Aucun utilisateur trouvé")
    }

}

//fonction de filtre
function show_events(){
    
}