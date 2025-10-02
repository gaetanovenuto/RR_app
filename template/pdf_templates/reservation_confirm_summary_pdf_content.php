<style>
    * {
        font-family: times;
    }
    h1 {
        color: black;
        font-size: 24pt;
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
<h1>Riepilogo prenotazione</h1>
<p class="header_paragraph">
    Gentile utente, <br>
    grazie per averci scelto. <br>
    Hai confermato la tua partecipazione. <br>
    Di seguito troverai un riepilogo della prenotazione:
</p>
<ul class="list-unstyled">
    <li>
        <span class="fw-bold">
            Nome evento:
        </span>
        <?= $event['name'] ?>
    </li>
    <li>
        <span class="fw-bold">
            Stanza:
        </span>
        <?= $room['name']?>
    </li>
    <li>
        <span class="fw-bold">
            Inizio evento:
        </span>
        <?= $event['starting_time']?>
    </li>
    <li>
        <span class="fw-bold">
            Fine evento:
        </span>
        <?= $event['ending_time']?>
    </li>
</ul>
<br>
<p>
    Per aggiungere l'evento al calendario, inquadra il QRCode.
</p>
<hr style="border-color: rgb(4, 15, 57)">