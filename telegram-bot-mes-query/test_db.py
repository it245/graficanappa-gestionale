import mysql.connector

c = mysql.connector.connect(
    host='127.0.0.1',
    user='root',
    password='GraficaNappa1919!',
    database='grafica_nappa'
)
cur = c.cursor(dictionary=True)
cur.execute(
    "SELECT commessa, cliente_nome, qta_richiesta, qta_ddt_vendita, numero_ddt_vendita "
    "FROM ordini WHERE commessa LIKE %s",
    ('0067235-%',)
)
rows = cur.fetchall()
print(f"Trovate {len(rows)} righe:")
for r in rows:
    print(r)
c.close()
