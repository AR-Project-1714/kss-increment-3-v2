            <div class="stats-row">
                <div class="stat-card">
                    <span class="stat-card__label">Laporan Hari ini</span>
                    <div class="stat-card__row">
                        <span class="stat-card__value">{{ number_format($stats['todayReports'] ?? 0, 0, ',', '.') }}</span>
                        <span class="stat-card__icon stat-card__icon--green"><i class="fi fi-sr-calendar"></i></span>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="stat-card__label">Laporan Pending</span>
                    <div class="stat-card__row">
                        <span class="stat-card__value">{{ number_format($stats['pendingReports'] ?? 0, 0, ',', '.') }}</span>
                        <span class="stat-card__icon stat-card__icon--orange"><i class="fi fi-sr-document"></i></span>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="stat-card__label">Laporan Bulan ini</span>
                    <div class="stat-card__row">
                        <span class="stat-card__value">{{ number_format($stats['monthlyReports'] ?? 0, 0, ',', '.') }}</span>
                        <span class="stat-card__icon stat-card__icon--cyan"><i class="fi fi-sr-folder"></i></span>
                    </div>
                </div>
                <div class="stat-card">
                    <span class="stat-card__label">Total Laporan</span>
                    <div class="stat-card__row">
                        <span class="stat-card__value">{{ number_format($stats['totalReports'] ?? 0, 0, ',', '.') }}</span>
                        <span class="stat-card__icon stat-card__icon--blue"><i class="fi fi-sr-book-alt"></i></span>
                    </div>
                </div>
            </div>
