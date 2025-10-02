<style>
    * {
        font-family: times;
    }
    h1 {
        color: black;
        font-size: 24pt;
        text-align: center;
    }
    p {
        font-size: 14pt;
    }
    .lowercase {
        text-transform: lowercase;
    }
    .uppercase {
        text-transform: uppercase;
    }
    .capitalize {
        text-transform: capitalize;
    }
    .list-unstyled {
        list-style-type: none;
    }
    .fw-bold {
        font-weight: bold;
    }
    li {
        font-size: 14px;
        padding-top: 10px;
    }
</style>
<h1>Biglietto d'ingresso</h1>
<p>
    Gentile partecipante,
    questo biglietto ti è stato inviato per accedere all'evento <?= $event['name'] ?>.
</p>
<p>
    Questo biglietto è stato inviato all'indirizzo email <?= $params['email'] ?>, <br>
    conservalo con cura.
</p>
<br>
<p>
    Presenta il codice a barre all'ingresso.
</p>
