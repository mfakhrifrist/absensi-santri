  </div><!-- /content -->
</main>
<script>
function updateJam() {
  const now = new Date();
  const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
  const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
  const jam = now.toTimeString().slice(0,8);
  const tgl = `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
  const el = document.getElementById('jam');
  if (el) el.textContent = `📅 ${tgl} — ${jam}`;
}
setInterval(updateJam, 1000);
updateJam();
</script>
</body>
</html>
