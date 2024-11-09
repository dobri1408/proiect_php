
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prezentare Proiect: Aplicație de Știri</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1, h2, h3 {
            color: #333;
        }
        ul {
            margin: 10px 0;
            padding: 0 20px;
        }
        footer {
            margin-top: 20px;
            text-align: center;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Prezentare Proiect: Aplicație Web de Știri</h1>
        <p>Acest proiect reprezintă o aplicație web de știri, dezvoltată pentru gestionarea și publicarea articolelor. Aplicația include două roluri principale: <strong>autori</strong> și <strong>admini</strong>.</p>
        
        <h2>Arhitectura aplicației</h2>
        <p>Arhitectura aplicației este bazată pe un model simplu de gestionare a știrilor, având următoarele componente principale:</p>
        <ul>
            <li><strong>Roluri:</strong>
                <ul>
                    <li><strong>Autor:</strong> Poate crea, edita și trimite știri pentru aprobare.</li>
                    <li><strong>Admin:</strong> Poate aproba sau respinge știri și le poate publica.</li>
                </ul>
            </li>
            <li><strong>Entități principale:</strong>
                <ul>
                    <li><strong>Știre:</strong> Include titlul, conținutul, autorul și statusul (aprobata, în așteptare, respinsă).</li>
                    <li><strong>Utilizator:</strong> Are rolul de autor sau admin.</li>
                </ul>
            </li>
            <li><strong>Procese:</strong>
                <ul>
                    <li>Adăugarea unei știri de către autor.</li>
                    <li>Aprobarea sau respingerea unei știri de către admin.</li>
                    <li>Publicarea automată a știrilor aprobate pe pagina principală.</li>
                </ul>
            </li>
        </ul>

        <h2>Descrierea bazei de date</h2>
        <p>Baza de date conține două tabele principale:</p>
        <ul>
            <li><strong>users:</strong> Informații despre utilizatori (id, username, parola, rol).</li>
            <li><strong>news:</strong> Detalii despre știri (id, titlu, conținut, id autor, status, data creării).</li>
        </ul>

        <h2>Soluția de implementare</h2>
        <p>Aplicația va fi implementată folosind:</p>
        <ul>
            <li><strong>Frontend:</strong> HTML, CSS, JavaScript.</li>
            <li><strong>Backend:</strong> PHP pentru gestionarea logicii aplicației și comunicarea cu baza de date.</li>
            <li><strong>Baza de date:</strong> MySQL pentru stocarea datelor despre utilizatori și știri.</li>
            <li><strong>Diagrama UML:</strong> Fluxurile vor fi documentate pentru a descrie relațiile dintre componente și procese.</li>
        </ul>

        <h2>Principalele componente PHP ale aplicației</h2>
        <p>Aplicația va fi structurată în următoarele componente principale:</p>
        <ul>
            <li><strong>Controllers:</strong> Gestionează logica aplicației:
                <ul>
                    <li><strong>NewsController:</strong> Gestionează știrile (adăugare, editare, aprobare).</li>
                    <li><strong>UserController:</strong> Gestionează autentificarea și gestionarea utilizatorilor.</li>
                </ul>
            </li>
            <li><strong>Models:</strong> Reprezintă entitățile bazei de date:
                <ul>
                    <li><strong>News:</strong> Model pentru știri, inclusiv titlu, conținut, autor, status.</li>
                    <li><strong>User:</strong> Model pentru utilizatori, incluzând roluri și informații de autentificare.</li>
                </ul>
            </li>
            <li><strong>Views:</strong> Reprezintă interfața cu utilizatorul:
                <ul>
                    <li>Formular pentru adăugarea știrilor.</li>
                    <li>Listarea știrilor aprobate pe pagina principală.</li>
                    <li>Interfață pentru administrarea utilizatorilor.</li>
                </ul>
            </li>
            <li><strong>Baza de date:</strong> Stochează informații structurate despre utilizatori și știri.</li>
        </ul>

        <h3>Arhitectura generală:</h3>
        <ul>
            <li>Utilizatorul trimite o cerere (de exemplu, adăugarea unei știri).</li>
            <li>Controller-ul gestionează cererea și comunică cu Model-ul pentru a accesa baza de date.</li>
            <li>Rezultatele sunt afișate prin intermediul View-urilor.</li>
        </ul>

        <h3>Fluxul procesului:</h3>
        <p>Un autor creează o știre -> Controller-ul o stochează în baza de date cu statusul "în așteptare" -> Adminul aprobă/respingea știrea -> Știrea devine publică dacă este aprobată.</p>

        <h2>Predare și Găzduire</h2>
        <p>Codul va fi publicat pe GitHub și aplicația va fi găzduită online folosind o platformă Heroku</p>

        <footer>
            <p>&copy; 2024 - Dobricean Ioan Dorian</p>
        </footer>
    </div>
</body>
</html>
