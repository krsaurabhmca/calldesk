<?php
// includes/footer.php
?>
            </main>
        </div>
    </div>
    <!-- Global Audio Player Modal -->
    <div id="audioModal" style="display: none; position: fixed; bottom: 20px; right: 20px; background: #fff; padding: 15px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); z-index: 9999; border: 1px solid var(--border); width: 300px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted);">CALL RECORDING</span>
            <button onclick="closeAudio()" style="background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-muted);">&times;</button>
        </div>
        <audio id="globalAudio" controls style="width: 100%; height: 35px;">
            <source id="audioSource" src="" type="audio/mpeg">
        </audio>
    </div>

    <script>
        function playRecord(path) {
            const modal = document.getElementById('audioModal');
            const audio = document.getElementById('globalAudio');
            const source = document.getElementById('audioSource');
            
            source.src = path;
            audio.load();
            modal.style.display = 'block';
            audio.play();
        }

        function closeAudio() {
            const modal = document.getElementById('audioModal');
            const audio = document.getElementById('globalAudio');
            audio.pause();
            modal.style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Placeholder for future JS logic
        });
    </script>
</body>
</html>
