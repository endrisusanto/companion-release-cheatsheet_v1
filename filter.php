<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filter Tasks</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2 class="mt-4">Filter Tasks by CSC</h2>
        <form method="post" action="">
            <div class="form-group">
                <label for="csc_version_up">CSC VERSION:</label>
                <input type="text" class="form-control" id="csc_version_up" name="csc_version_up">
            </div>
            <button type="submit" name="submit" class="btn btn-primary">Filter</button>
        </form>

        <?php
        if (isset($_POST['submit'])) {
            $servername = "localhost";
            $username = "root";
            $pass = "";
            $dbname = "companion_release_db";

            // Membuat koneksi
            $conn = new mysqli($servername, $username, $pass, $dbname);

            // Memeriksa koneksi
            if ($conn->connect_error) {
                die("Connection failed: " . $conn->connect_error);
            }

            // Mengambil data dari form
            $csc_version_up = isset($_POST['csc_version_up']) ? $_POST['csc_version_up'] : '';

            // Membuat query
            $sql = "SELECT * FROM release_cheatsheets WHERE csc_version_up LIKE ? ORDER BY id DESC LIMIT 1";

            // Prepare and bind
            $stmt = $conn->prepare($sql);
            $search_term = '%' . $csc_version_up . '%';
            $stmt->bind_param("s", $search_term);

            // Eksekusi query
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo "<h2 class='mt-4'>Results:</h2>";
                echo "<table class='table table-bordered'>
                                <thead>
                                    <tr>
                                        <th hidden>ID</th>
                                        <th>model</th>
                                        <th>ap</th>
                                        <th>cp</th>
                                        <th>csc</th>
                                        <th>ole_version</th>
                                        <th>prev qb csc</th>
                                        <th>user qb csc</th>
                                        <th>eng qb csc</th>
                                        <th>CSC Version Up</th>
                                        <th>Last 5 Chars CSC</th>
                                        <th>Customer</th>
                                        <th>Release Note</th>
                                        <th>Partial CL</th>
                                    </tr>
                                </thead>
                                <tbody>";
                // Karena kita hanya ingin 1 baris, kita bisa langsung mengambilnya
                $row = $result->fetch_assoc();

                // Mendapatkan 5 karakter terakhir dari csc_version_up
                $last_5_chars_csc = substr($row["csc_version_up"], -5);
                
                // Mendapatkan 3 karakter sebelum 5 karakter terakhir dari csc_version_up
                // Pastikan string cukup panjang
                $ole_chars = '';
                if (strlen($row["csc_version_up"]) >= 8) { // Total 3 (for OLE) + 5 (for last 5 chars) = 8 characters needed at least
                    $ole_chars = substr($row["csc_version_up"], -8, 3);
                }


                echo "<tr>
                                <td hidden>" . htmlspecialchars($row["id"]) . "</td>
                                <td id='model'>" . htmlspecialchars($row["model"]) . "</td>
                                <td id='ap'>" . htmlspecialchars($row["ap"]) . "</td>
                                <td id='cp'>" . htmlspecialchars($row["cp"]) . "</td>
                                <td id='csc'>" . htmlspecialchars($row["csc"]) . "</td>
                                <td id='ole_version'>" . htmlspecialchars($row["ole_version"]) . "</td>
                                <td id='qb_user'>" . htmlspecialchars($row["qb_user"]) . "</td>
                                <td id='qb_csc_user_xid'>" . htmlspecialchars($row["qb_csc_user_xid"]) . "</td>
                                <td id='qb_csc_eng'>" . htmlspecialchars($row["qb_csc_eng"]) . "</td>
                                <td id='csc_version_up'>" . htmlspecialchars($row["csc_version_up"]) . "</td>
                                <td id='last_5_chars_csc'>" . htmlspecialchars($last_5_chars_csc) . "</td>
                                <td id='ole_3_chars'>" . htmlspecialchars($ole_chars) . "</td>
                                <td id='release_note_format'>" . htmlspecialchars($row["release_note_format"]) . "</td>
                                <td id='partial_cl'>" . htmlspecialchars($row["partial_cl"]) . "</td>
                            </tr>";
                echo "</tbody>
                            </table>";
            } else {
                echo "<p class='mt-4'>0 results</p>";
            }

            $stmt->close();
            $conn->close();
        }
        ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>