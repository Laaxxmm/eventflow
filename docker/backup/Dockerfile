# Nightly backups: mysqldump (from the MySQL 8.4 image, matching the server)
# plus rclone to any S3-compatible bucket (AWS S3, Cloudflare R2, Backblaze B2,
# DigitalOcean Spaces, ...). Configured entirely from env; see deploy/env.example.
FROM rclone/rclone:1.68 AS rclone

FROM mysql:8.4

COPY --from=rclone /usr/local/bin/rclone /usr/local/bin/rclone
COPY backup.sh restore-test.sh schedule.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/backup.sh /usr/local/bin/restore-test.sh /usr/local/bin/schedule.sh \
    && touch /etc/rclone.conf

# Remotes come from RCLONE_CONFIG_<NAME>_* env vars; the file just stays empty.
ENV RCLONE_CONFIG=/etc/rclone.conf

ENTRYPOINT []
CMD ["schedule.sh"]
